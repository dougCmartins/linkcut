<?php

declare(strict_types=1);

namespace Domain\Catalog\Tests\Feature;

use Domain\Catalog\Models\Product;
use Domain\Catalog\Support\ProductLimits;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Actions\PruneUnusedImages;
use Domain\Settings\Data\PruneImagesData;
use Domain\Settings\Models\Image;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The product showcase: the panel edits it. The public page does not list it. */
final class CatalogTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
        Storage::fake(Image::DISK);
    }

    public function test_the_owner_creates_edits_and_deletes_a_product(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);

        // Act
        $created = $this->postJson('/api/products', ['name' => ' Pomada ', 'price_in_cents' => 4500, 'description' => 'Efeito matte']);
        $id = $created->json('id');
        $updated = $this->putJson("/api/products/{$id}", ['name' => 'Óleo', 'price_in_cents' => 3200, 'description' => null, 'is_active' => false]);
        $deleted = $this->deleteJson("/api/products/{$id}");
        $listed = $this->getJson('/api/products');

        // Assert
        $created->assertCreated()
            ->assertJsonPath('name', 'Pomada')
            ->assertJsonPath('price_in_cents', 4500)
            ->assertJsonPath('description', 'Efeito matte')
            ->assertJsonPath('is_active', true)
            ->assertJsonPath('position', 1)
            ->assertJsonPath('image', null);
        $updated->assertOk()->assertJsonPath('name', 'Óleo')->assertJsonPath('is_active', false)->assertJsonPath('position', 1);
        $deleted->assertNoContent();
        $listed->assertOk()->assertJsonCount(0);
    }

    public function test_reordering_uses_the_order_route_and_not_the_product_id(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);
        $first = $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500])->json('id');
        $second = $this->postJson('/api/products', ['name' => 'Óleo', 'price_in_cents' => 3200])->json('id');

        // Act
        $reordered = $this->putJson('/api/products/order', ['ids' => [$second, $first]]);

        // Assert
        $reordered->assertOk()
            ->assertJsonPath('0.id', $second)
            ->assertJsonPath('0.position', 1)
            ->assertJsonPath('1.id', $first)
            ->assertJsonPath('1.position', 2);
        $this->assertNotSame('PRODUCT_NOT_FOUND', $reordered->json('code'));
    }

    public function test_an_order_with_a_missing_repeated_or_foreign_id_is_refused(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $other = $this->barbershop('outro');
        $this->actingAsOwner($other);
        $foreign = $this->postJson('/api/products', ['name' => 'Deles', 'price_in_cents' => 1000])->json('id');
        $this->actingAsOwner($owner);
        $first = $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500])->json('id');
        $this->postJson('/api/products', ['name' => 'Óleo', 'price_in_cents' => 3200])->assertCreated();

        // Act
        $missing = $this->putJson('/api/products/order', ['ids' => [$first]]);
        $repeated = $this->putJson('/api/products/order', ['ids' => [$first, $first]]);
        $foreignOrder = $this->putJson('/api/products/order', ['ids' => [$first, $foreign]]);

        // Assert
        $missing->assertStatus(422)->assertJsonPath('code', 'PRODUCT_ORDER_INVALID');
        $repeated->assertStatus(422)->assertJsonPath('code', 'PRODUCT_ORDER_INVALID');
        $foreignOrder->assertStatus(422)->assertJsonPath('code', 'PRODUCT_ORDER_INVALID');
    }

    public function test_another_tenant_cannot_edit_or_delete_a_product(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $other = $this->barbershop('outro');
        $this->actingAsOwner($owner);
        $id = $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500])->json('id');
        $this->actingAsOwner($other);

        // Act
        $updated = $this->putJson("/api/products/{$id}", ['name' => 'Roubo', 'price_in_cents' => 1]);
        $deleted = $this->deleteJson("/api/products/{$id}");

        // Assert
        $updated->assertNotFound()->assertJsonPath('code', 'PRODUCT_NOT_FOUND');
        $deleted->assertNotFound()->assertJsonPath('code', 'PRODUCT_NOT_FOUND');
        $this->assertSame('Pomada', Product::withoutTenantScope()->findOrFail($id)->name);
    }

    public function test_an_unknown_or_foreign_image_is_refused(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $other = $this->barbershop('outro');
        $this->actingAsOwner($owner);
        $imageId = $this->post('/api/settings/images', ['file' => $this->pngFile()], ['Accept' => 'application/json'])->json('id');
        $this->actingAsOwner($other);

        // Act
        $foreign = $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500, 'image_id' => $imageId]);
        $this->actingAsOwner($owner);
        $missing = $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500, 'image_id' => 999999]);

        // Assert
        $foreign->assertStatus(422)->assertJsonPath('code', 'IMAGE_NOT_FOUND');
        $missing->assertStatus(422)->assertJsonPath('code', 'IMAGE_NOT_FOUND');
    }

    public function test_the_public_page_does_not_include_products(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);
        $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500])->assertCreated();
        $this->app['auth']->forgetGuards();

        // Act
        $page = $this->getJson('/api/public/rafael');

        // Assert
        $page->assertOk();
        $this->assertArrayNotHasKey('products', $page->json());
    }

    public function test_the_fifty_first_product_is_refused(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, function (): void {
            $rows = [];
            for ($position = 1; $position <= ProductLimits::PRODUCTS_LIMIT; $position++) {
                $rows[] = [
                    'name' => "Product {$position}",
                    'price_in_cents' => 100,
                    'description' => null,
                    'image_id' => null,
                    'is_active' => true,
                    'position' => $position,
                ];
            }
            Product::insertForCurrentTenant($rows);
        });
        $this->actingAsOwner($owner);

        // Act
        $refused = $this->postJson('/api/products', ['name' => 'A mais', 'price_in_cents' => 100]);

        // Assert
        $refused->assertStatus(422)->assertJsonPath('code', 'PRODUCTS_LIMIT_REACHED');
        $this->assertSame(ProductLimits::PRODUCTS_LIMIT, Product::withoutTenantScope()->count());
    }

    public function test_a_description_with_a_link_or_a_tag_is_cleaned(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);

        // Act
        $created = $this->postJson('/api/products', [
            'name' => 'Pomada',
            'price_in_cents' => 4500,
            'description' => '  Corte <b>ótimo</b> https://evil.example  ',
        ]);
        $empty = $this->postJson('/api/products', ['name' => 'Óleo', 'price_in_cents' => 3200, 'description' => '   ']);

        // Assert
        $created->assertCreated()->assertJsonPath('description', 'Corte ótimo');
        $empty->assertCreated()->assertJsonPath('description', null);
    }

    public function test_pruning_keeps_a_product_photo(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);
        $used = $this->post('/api/settings/images', ['file' => $this->pngFile()], ['Accept' => 'application/json'])->json('id');
        $unused = $this->post('/api/settings/images', ['file' => $this->pngFile()], ['Accept' => 'application/json'])->json('id');
        $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500, 'image_id' => $used])->assertCreated();
        $unusedPath = Image::withoutTenantScope()->findOrFail($unused)->path;
        $this->travel(25)->hours();

        // Act
        $pruned = app(PruneUnusedImages::class)->handle(new PruneImagesData);

        // Assert
        $this->assertSame(1, $pruned);
        $this->assertTrue(Image::withoutTenantScope()->whereKey($used)->exists());
        $this->assertFalse(Image::withoutTenantScope()->whereKey($unused)->exists());
        Storage::disk(Image::DISK)->assertMissing($unusedPath);
    }

    public function test_the_barbershop_export_includes_products(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->actingAsOwner($owner);
        $this->postJson('/api/products', ['name' => 'Pomada', 'price_in_cents' => 4500])->assertCreated();

        // Act
        $export = $this->get('/api/account/export', ['Accept' => 'application/json']);

        // Assert
        $export->assertOk()->assertJsonPath('products.0.name', 'Pomada')->assertJsonPath('products.0.price_in_cents', 4500);
    }

    private function pngFile(): UploadedFile
    {
        $image = imagecreatetruecolor(10, 10);
        $path = tempnam(sys_get_temp_dir(), 'png');
        imagepng($image, $path);

        return new UploadedFile($path, 'foto.png', 'image/png', null, true);
    }
}

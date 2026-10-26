<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Feature;

use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Link previews for WhatsApp, Facebook and X, which read the HTML and do not run the SPA. */
final class ShareCardTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    public function test_the_preview_has_the_barbershop_name_bio_photo_and_link(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, function (): void {
            $image = Image::query()->create(['path' => 'images/rafa.webp', 'mime' => 'image/webp', 'size' => 10, 'width' => 400, 'height' => 400]);
            ProfileSettings::query()->firstOrFail()->update(['name' => 'Rafael Costa', 'bio' => 'Cortes clássicos', 'avatar_image_id' => $image->id]);
        });

        // Act
        $response = $this->get('/api/og/rafael');

        // Assert
        $response->assertOk()->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $html = $response->getContent();
        $this->assertStringContainsString('<meta property="og:title" content="Rafael Costa">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Cortes clássicos">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="http://localhost:5173/api/og/rafael/imagem">', $html);
        $this->assertStringNotContainsString('http-equiv="refresh"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="http://localhost:5173/rafael">', $html);
    }

    public function test_the_preview_photo_is_served_as_jpeg_and_kept_for_the_next_time(): void
    {
        // Arrange
        Storage::fake(Image::DISK);
        $owner = $this->barbershop();
        $webp = UploadedFile::fake()->image('rafa.webp', 400, 400);
        Storage::disk(Image::DISK)->put('tenants/1/images/rafa.webp', (string) file_get_contents($webp->getRealPath()));
        app(CurrentTenant::class)->run($owner->tenant_id, function (): void {
            $image = Image::query()->create(['path' => 'tenants/1/images/rafa.webp', 'mime' => 'image/webp', 'size' => 10, 'width' => 400, 'height' => 400]);
            ProfileSettings::query()->firstOrFail()->update(['avatar_image_id' => $image->id]);
        });

        // Act
        $response = $this->get('/api/og/rafael/imagem');

        // Assert
        $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame("\xFF\xD8", substr((string) $response->getContent(), 0, 2));
        Storage::disk(Image::DISK)->assertExists('tenants/1/images/rafa.preview.jpg');
    }

    public function test_a_shared_booking_link_previews_the_barbershop_and_a_page_without_photo_has_no_image(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => ProfileSettings::query()->firstOrFail()->update(['name' => 'Rafael Costa']));

        // Act
        $html = $this->get('/api/og/rafael/agendar')->assertOk()->getContent();
        $image = $this->get('/api/og/rafael/imagem');

        // Assert
        $this->assertStringContainsString('<meta property="og:title" content="Rafael Costa">', $html);
        $this->assertStringNotContainsString('og:image', $html);
        $image->assertNotFound();
    }

    public function test_the_portfolio_preview_uses_its_quote_and_cover(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, function (): void {
            ProfileSettings::query()->firstOrFail()->update(['name' => 'Rafael Costa']);
            PortfolioSettings::query()->firstOrFail()->update(['quote' => 'Cada corte conta uma história']);
        });

        // Act
        $html = $this->get('/api/og/rafael/portfolio')->assertOk()->getContent();

        // Assert
        $this->assertStringContainsString('content="Portfólio de Rafael Costa"', $html);
        $this->assertStringContainsString('content="Cada corte conta uma história"', $html);
        $this->assertStringContainsString('content="http://localhost:5173/rafael/portfolio"', $html);
    }

    public function test_texts_typed_by_the_barber_are_escaped(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => ProfileSettings::query()->firstOrFail()->update([
            'name' => 'Rafa "Navalha"', 'bio' => '<script>alert(1)</script>',
        ]));

        // Act
        $html = $this->get('/api/og/rafael')->assertOk()->getContent();

        // Assert
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('content="Rafa &quot;Navalha&quot;"', $html);
    }

    public function test_an_unknown_barbershop_is_not_found(): void
    {
        // Act
        $response = $this->get('/api/og/nao-existe');

        // Assert
        $response->assertNotFound();
    }
}

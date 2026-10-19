<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Settings\Actions\PruneUnusedImages;
use Domain\Settings\Data\PruneImagesData;
use Domain\Settings\Models\Image;
use Domain\Settings\Tests\Concerns\SettingsPayloads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class ImageUploadTest extends TestCase
{
    use RefreshDatabase;
    use SettingsPayloads;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Image::DISK);
    }

    private function pngFile(int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        $path = tempnam(sys_get_temp_dir(), 'png');
        imagepng($image, $path);

        return new UploadedFile($path, 'foto.png', 'image/png', null, true);
    }

    public function test_an_upload_is_stored_as_webp_of_at_most_1600_px(): void
    {
        // Arrange
        Passport::actingAs(User::factory()->create());

        // Act
        $response = $this->post('/api/settings/images', ['file' => $this->pngFile(3200, 1000)], ['Accept' => 'application/json']);

        // Assert
        $response->assertCreated();
        $image = Image::withoutTenantScope()->findOrFail($response->json('id'));
        $this->assertSame([1600, 500, 'image/webp'], [$image->width, $image->height, $image->mime]);
        $this->assertStringEndsWith('.webp', $image->path);
        Storage::disk(Image::DISK)->assertExists($image->path);
        $this->assertStringContainsString($image->path, $response->json('url'));
    }

    public function test_only_readable_images_up_to_5_mb_are_accepted(): void
    {
        // Arrange
        Passport::actingAs(User::factory()->create());
        $text = UploadedFile::fake()->createWithContent('foto.png', 'not an image');
        $huge = UploadedFile::fake()->create('foto.jpg', 6000, 'image/jpeg');

        // Act
        $textResponse = $this->post('/api/settings/images', ['file' => $text], ['Accept' => 'application/json']);
        $hugeResponse = $this->post('/api/settings/images', ['file' => $huge], ['Accept' => 'application/json']);

        // Assert
        $textResponse->assertUnprocessable()->assertJsonPath('code', 'INVALID_IMAGE');
        $hugeResponse->assertUnprocessable()->assertJsonPath('errors.file.0', 'max');
    }

    public function test_settings_cannot_use_an_image_of_another_barbershop(): void
    {
        // Arrange
        Passport::actingAs(User::factory()->create());
        $foreignImageId = $this->post('/api/settings/images', ['file' => $this->pngFile(10, 10)], ['Accept' => 'application/json'])->json('id');
        $this->app['auth']->forgetGuards();
        Passport::actingAs(User::factory()->create());

        // Act
        $response = $this->putJson('/api/settings/profile', $this->profilePayload(['avatar_image_id' => $foreignImageId]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'IMAGE_NOT_FOUND');
    }

    public function test_the_saved_avatar_is_returned_with_its_url(): void
    {
        // Arrange
        Passport::actingAs(User::factory()->create());
        $image = $this->post('/api/settings/images', ['file' => $this->pngFile(10, 10)], ['Accept' => 'application/json'])->json();

        // Act
        $response = $this->putJson('/api/settings/profile', $this->profilePayload(['avatar_image_id' => $image['id']]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('avatar', $image);
    }

    public function test_pruning_deletes_old_unused_uploads_and_keeps_the_used_ones(): void
    {
        // Arrange
        Passport::actingAs(User::factory()->create());
        $used = $this->post('/api/settings/images', ['file' => $this->pngFile(10, 10)], ['Accept' => 'application/json'])->json('id');
        $unused = $this->post('/api/settings/images', ['file' => $this->pngFile(10, 10)], ['Accept' => 'application/json'])->json('id');
        $this->putJson('/api/settings/profile', $this->profilePayload(['avatar_image_id' => $used]))->assertOk();
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
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ImageData;
use Domain\Settings\Data\UploadImageData;
use Domain\Settings\Models\Image;
use Domain\Settings\Support\ImageProcessor;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\Log;

/**
 * Stores an uploaded image (WebP, at most 1600 px). It only shows up on the public
 * pages after a settings save references it; unused uploads are pruned later.
 */
final class UploadImage
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(UploadImageData $data): ImageData
    {
        $stored = ImageProcessor::storeAsWebp($data->file, $this->currentTenant->id(), Image::DISK);
        $image = Image::query()->create($stored);

        Log::info('settings_image_uploaded', ['image_id' => $image->id, 'size' => $image->size]);

        return new ImageData(id: $image->id, url: $image->url());
    }
}

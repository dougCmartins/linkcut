<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\PruneImagesData;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\PortfolioPhoto;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\ProfileSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes uploads that no settings use anymore (replaced, removed, or uploaded and never saved).
 * Runs for every tenant (scheduled command), so it reads without the tenant scope on purpose.
 */
final class PruneUnusedImages
{
    public function handle(PruneImagesData $data): int
    {
        $inUse = array_merge(
            ProfileSettings::withoutTenantScope()->pluck('avatar_image_id')->all(),
            ProfileSettings::withoutTenantScope()->pluck('background_image_id')->all(),
            PortfolioSettings::withoutTenantScope()->pluck('cover_image_id')->all(),
            PortfolioPhoto::withoutTenantScope()->pluck('image_id')->all(),
        );

        $pruned = 0;
        Image::withoutTenantScope()
            ->whereNotIn('id', array_filter($inUse))
            ->where('created_at', '<', now()->subHours($data->older_than_hours))
            ->chunkById(200, function ($images) use (&$pruned): void {
                foreach ($images as $image) {
                    Storage::disk(Image::DISK)->delete($image->path);
                    $image->delete();
                    $pruned++;
                }
            });

        Log::info('settings_images_pruned', ['count' => $pruned]);

        return $pruned;
    }
}

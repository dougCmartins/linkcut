<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\PruneImagesData;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\PortfolioPhoto;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\ProfileSettings;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes uploads that no settings use anymore (replaced, removed, or uploaded and never saved).
 * Runs for every tenant (scheduled command), so it reads without the tenant scope on purpose.
 * "Not used" is NOT EXISTS against each table (no list of every id in use loaded in memory).
 */
final class PruneUnusedImages
{
    public function handle(PruneImagesData $data): int
    {
        $pruned = 0;
        Image::withoutTenantScope()
            ->where('created_at', '<', now()->subHours($data->older_than_hours))
            ->whereNotExists(fn (QueryBuilder $query) => $query->selectRaw('1')->from((new ProfileSettings)->getTable(), 'profile')
                ->where(fn (QueryBuilder $used) => $used->whereColumn('profile.avatar_image_id', 'images.id')
                    ->orWhereColumn('profile.background_image_id', 'images.id')))
            ->whereNotExists(fn (QueryBuilder $query) => $query->selectRaw('1')->from((new PortfolioSettings)->getTable(), 'portfolio')
                ->whereColumn('portfolio.cover_image_id', 'images.id'))
            ->whereNotExists(fn (QueryBuilder $query) => $query->selectRaw('1')->from((new PortfolioPhoto)->getTable(), 'photo')
                ->whereColumn('photo.image_id', 'images.id'))
            ->chunkById(200, function ($images) use (&$pruned): void {
                Storage::disk(Image::DISK)->delete($images->pluck('path')->all());
                Image::withoutTenantScope()->whereKey($images->modelKeys())->delete();
                $pruned += $images->count();
            });

        Log::info('settings_images_pruned', ['count' => $pruned]);

        return $pruned;
    }
}

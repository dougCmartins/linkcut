<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Review\Actions;

use Domain\Review\Actions\ListBarberReviews;
use Domain\Review\Actions\UpdateFeaturedReviews;
use Domain\Review\Data\BarberReviewData;
use Domain\Review\Data\FeatureReviewsData;
use Domain\Settings\Support\PublicSettingsCache;
use Spatie\LaravelData\DataCollection;

/** Saves the order of the comments on the page and returns the barber's list. */
final class FeatureBarberReviews
{
    public function __construct(
        private readonly UpdateFeaturedReviews $updateFeatured,
        private readonly ListBarberReviews $listReviews,
        private readonly PublicSettingsCache $cache,
    ) {}

    /** @return DataCollection<int, BarberReviewData> */
    public function handle(FeatureReviewsData $data): DataCollection
    {
        $this->updateFeatured->handle($data);
        $this->cache->forget();

        return $this->listReviews->handle();
    }
}

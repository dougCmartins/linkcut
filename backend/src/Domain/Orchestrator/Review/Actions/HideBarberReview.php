<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Review\Actions;

use Domain\Review\Actions\HideReview;
use Domain\Review\Data\BarberReviewData;
use Domain\Review\Data\ReviewIdData;
use Domain\Settings\Support\PublicSettingsCache;

/** Hides a review of the current barbershop and drops it from the public page cache. */
final class HideBarberReview
{
    public function __construct(
        private readonly HideReview $hideReview,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(ReviewIdData $data): BarberReviewData
    {
        $review = $this->hideReview->handle($data);
        $this->cache->forget();

        return $review;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Actions;

use Domain\Orchestrator\PublicPage\Data\PublicVisitData;
use Domain\Review\Actions\ShowPublicReviews;
use Domain\Review\Data\PublicReviewsData;
use Domain\Review\Data\ReviewVisibilityData;
use Domain\Settings\Actions\ShowPortfolioReviewOptions;
use Domain\Settings\Actions\ShowPublicPage;
use Domain\Settings\Support\PublicSettingsCache;

/** Joins the cached public page with the cached reviews. The share card keeps using the page alone. */
final class ShowPublicVisit
{
    public function __construct(
        private readonly ShowPublicPage $showPage,
        private readonly ShowPortfolioReviewOptions $showOptions,
        private readonly ShowPublicReviews $showReviews,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(): PublicVisitData
    {
        $page = $this->showPage->handle();
        $reviews = $this->cache->remember(PublicSettingsCache::REVIEWS, function (): PublicReviewsData {
            $options = $this->showOptions->handle();

            return $this->showReviews->handle(new ReviewVisibilityData(
                show_rating: $options->show_rating,
                show_reviews: $options->show_reviews,
            ));
        });

        return new PublicVisitData(
            profile: $page->profile,
            portfolio: $page->portfolio,
            theme: $page->theme,
            rating: $reviews->rating,
            featured_reviews: $reviews->featured_reviews,
        );
    }
}

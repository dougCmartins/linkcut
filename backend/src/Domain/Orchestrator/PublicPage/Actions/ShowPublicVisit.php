<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Actions;

use Domain\Orchestrator\PublicPage\Data\PublicVisitData;
use Domain\Orchestrator\PublicPage\Support\BrandCache;
use Domain\Review\Actions\ShowPublicReviews;
use Domain\Review\Data\PublicReviewsData;
use Domain\Review\Data\ReviewVisibilityData;
use Domain\Settings\Actions\ShowPortfolioReviewOptions;
use Domain\Settings\Actions\ShowPublicPage;
use Domain\Settings\Support\PublicSettingsCache;
use Domain\Tenant\Actions\ShowBrandSlugOfCurrentTenant;
use Domain\Tenant\Context\CurrentTenant;

/**
 * Joins the cached public page with the cached reviews, and says whether this page is a brand link (the main unit of a
 * brand with branches, which shows the branch picker). The share card keeps using the page alone.
 */
final class ShowPublicVisit
{
    public function __construct(
        private readonly ShowPublicPage $showPage,
        private readonly ShowPortfolioReviewOptions $showOptions,
        private readonly ShowPublicReviews $showReviews,
        private readonly PublicSettingsCache $cache,
        private readonly ShowBrandSlugOfCurrentTenant $showBrandSlug,
        private readonly BrandCache $brandCache,
        private readonly CurrentTenant $currentTenant,
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
            brand_slug: $this->brandCache->brandOf($this->currentTenant->id(), fn (): ?string => $this->showBrandSlug->handle()),
        );
    }
}

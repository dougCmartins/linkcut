<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Data;

use Domain\Review\Data\FeaturedReviewData;
use Domain\Review\Data\PublicRatingData;
use Domain\Settings\Data\PublicPortfolioData;
use Domain\Settings\Data\PublicProfileData;
use Domain\Settings\Data\ThemeSettingsData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** The public page plus the rating and the comments. The JSON stays flat, like the page alone. */
final class PublicVisitData extends Data
{
    /** @param DataCollection<int, FeaturedReviewData> $featured_reviews */
    public function __construct(
        public readonly PublicProfileData $profile,
        public readonly PublicPortfolioData $portfolio,
        public readonly ThemeSettingsData $theme,
        public readonly PublicRatingData $rating,
        #[DataCollectionOf(FeaturedReviewData::class)]
        public readonly DataCollection $featured_reviews,
    ) {}
}

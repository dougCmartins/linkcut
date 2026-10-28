<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** What the public page adds to the barbershop: the average and up to three comments. */
final class PublicReviewsData extends Data
{
    /** @param DataCollection<int, FeaturedReviewData> $featured_reviews */
    public function __construct(
        public readonly PublicRatingData $rating,
        #[DataCollectionOf(FeaturedReviewData::class)]
        public readonly DataCollection $featured_reviews,
    ) {}
}

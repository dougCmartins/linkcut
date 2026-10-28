<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\FeaturedReviewData;
use Domain\Review\Data\PublicRatingData;
use Domain\Review\Data\PublicReviewsData;
use Domain\Review\Data\ReviewVisibilityData;
use Domain\Review\Models\Review;
use Spatie\LaravelData\DataCollection;

/** The average and the featured comments. An option that is off does not read the reviews table. */
final class ShowPublicReviews
{
    public function handle(ReviewVisibilityData $visibility): PublicReviewsData
    {
        return new PublicReviewsData(
            rating: $visibility->show_rating ? $this->rating() : new PublicRatingData(average: null, count: 0),
            featured_reviews: $visibility->show_reviews ? $this->featured() : new DataCollection(FeaturedReviewData::class, []),
        );
    }

    private function rating(): PublicRatingData
    {
        $row = Review::query()->whereNull('hidden_at')->selectRaw('count(*) as review_count, avg(rating) as rating_average')->first();
        $count = (int) ($row->review_count ?? 0);

        return new PublicRatingData(
            average: $count > 0 ? round((float) $row->rating_average, 1) : null,
            count: $count,
        );
    }

    /** @return DataCollection<int, FeaturedReviewData> */
    private function featured(): DataCollection
    {
        $reviews = Review::query()
            ->whereNull('hidden_at')
            ->whereNotNull('featured_position')
            ->whereNotNull('comment')
            ->orderBy('featured_position')
            ->limit(3)
            ->get(['author_first_name', 'rating', 'comment'])
            ->map(fn (Review $review): FeaturedReviewData => new FeaturedReviewData(
                first_name: $review->author_first_name,
                rating: $review->rating,
                comment: (string) $review->comment,
            ))
            ->all();

        return new DataCollection(FeaturedReviewData::class, $reviews);
    }
}

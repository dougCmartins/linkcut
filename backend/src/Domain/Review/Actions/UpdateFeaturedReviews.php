<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\FeatureReviewsData;
use Domain\Review\Exceptions\ReviewNotFeaturedException;
use Domain\Review\Exceptions\ReviewNotFoundException;
use Domain\Review\Models\Review;
use Illuminate\Support\Facades\DB;

/**
 * Puts up to three reviews with a comment on the page, in the order received.
 * Positions are cleared first so swapping 1 and 2 does not hit the unique index.
 */
final class UpdateFeaturedReviews
{
    public function handle(FeatureReviewsData $data): void
    {
        $ids = array_values(array_map(intval(...), $data->review_ids));
        if (count($ids) > 3 || count($ids) !== count(array_unique($ids))) {
            throw new ReviewNotFeaturedException;
        }

        $reviews = Review::query()->whereIn('id', $ids)->get()->keyBy('id');
        if ($reviews->count() !== count($ids)) {
            throw new ReviewNotFoundException;
        }
        foreach ($ids as $id) {
            $review = $reviews->get($id);
            if ($review === null || $review->hidden_at !== null || $review->comment === null || $review->comment === '') {
                throw new ReviewNotFeaturedException;
            }
        }

        DB::transaction(function () use ($ids): void {
            Review::query()->whereNotNull('featured_position')->update(['featured_position' => null]);
            foreach ($ids as $index => $id) {
                Review::query()->whereKey($id)->update(['featured_position' => $index + 1]);
            }
        });
    }
}

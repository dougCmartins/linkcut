<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\BarberReviewData;
use Domain\Review\Data\ReviewIdData;
use Domain\Review\Exceptions\ReviewNotFoundException;
use Domain\Review\Models\Review;
use Domain\Tenant\Context\CurrentTenant;

/** Hides a review and takes it off the page. The barber cannot publish it again. */
final class HideReview
{
    public function __construct(private readonly CurrentTenant $tenant) {}

    public function handle(ReviewIdData $data): BarberReviewData
    {
        $review = Review::query()->find($data->review_id) ?? throw new ReviewNotFoundException;
        $review->update([
            'hidden_at' => $review->hidden_at ?? now(),
            'featured_position' => null,
        ]);

        return $this->present($review->refresh());
    }

    private function present(Review $review): BarberReviewData
    {
        $timezone = $this->tenant->timezone() ?? (string) config('app.timezone');

        return new BarberReviewData(
            id: $review->id,
            booking_request_id: $review->booking_request_id,
            rating: $review->rating,
            comment: $review->comment,
            first_name: $review->author_first_name,
            date: $review->published_at->timezone($timezone)->toDateString(),
            hidden_at: $review->hidden_at?->toIso8601String(),
            featured_position: $review->featured_position,
        );
    }
}

<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\BarberReviewData;
use Domain\Review\Models\Review;
use Domain\Tenant\Context\CurrentTenant;
use Spatie\LaravelData\DataCollection;

/** Every review of the current barbershop, newest first. Hidden ones stay in the list. */
final class ListBarberReviews
{
    public function __construct(private readonly CurrentTenant $tenant) {}

    /** @return DataCollection<int, BarberReviewData> */
    public function handle(): DataCollection
    {
        $timezone = $this->tenant->timezone() ?? (string) config('app.timezone');
        $reviews = Review::query()->orderByDesc('published_at')->orderByDesc('id')->get()
            ->map(fn (Review $review): BarberReviewData => new BarberReviewData(
                id: $review->id,
                booking_request_id: $review->booking_request_id,
                rating: $review->rating,
                comment: $review->comment,
                first_name: $review->author_first_name,
                date: $review->published_at->timezone($timezone)->toDateString(),
                hidden_at: $review->hidden_at?->toIso8601String(),
                featured_position: $review->featured_position,
            ))
            ->all();

        return BarberReviewData::collect($reviews, DataCollection::class);
    }
}

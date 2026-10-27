<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\ClientReviewsQueryData;
use Domain\Review\Data\ExportedReviewData;
use Domain\Review\Models\Review;
use Spatie\LaravelData\DataCollection;

/** Every review of one client, in any barbershop, for the data export. */
final class ListClientReviews
{
    /** @return DataCollection<int, ExportedReviewData> */
    public function handle(ClientReviewsQueryData $data): DataCollection
    {
        $reviews = Review::withoutTenantScope()
            ->where('client_id', $data->client_id)
            ->orderBy('published_at')
            ->get()
            ->map(fn (Review $review): ExportedReviewData => new ExportedReviewData(
                booking_request_id: $review->booking_request_id,
                rating: $review->rating,
                comment: $review->comment,
                published_at: $review->published_at->toIso8601String(),
                hidden_at: $review->hidden_at?->toIso8601String(),
            ))
            ->all();

        return ExportedReviewData::collect($reviews, DataCollection::class);
    }
}

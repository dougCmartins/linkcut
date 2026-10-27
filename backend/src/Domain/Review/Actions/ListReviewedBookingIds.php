<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\BookingIdsData;
use Domain\Review\Models\Review;

/** Which of these visits already have a review. One query, no tenant (the client area has none). */
final class ListReviewedBookingIds
{
    public function handle(BookingIdsData $data): BookingIdsData
    {
        if ($data->ids === []) {
            return new BookingIdsData(ids: []);
        }

        $ids = Review::withoutTenantScope()
            ->whereIn('booking_request_id', $data->ids)
            ->pluck('booking_request_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return new BookingIdsData(ids: $ids);
    }
}

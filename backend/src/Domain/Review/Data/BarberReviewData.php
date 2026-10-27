<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** One review as the barber sees it in the panel. */
final class BarberReviewData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $booking_request_id,
        public readonly int $rating,
        public readonly ?string $comment,
        public readonly string $first_name,
        /** "YYYY-MM-DD" in the barbershop's timezone. */
        public readonly string $date,
        public readonly ?string $hidden_at,
        public readonly ?int $featured_position,
    ) {}
}

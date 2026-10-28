<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** A review in the client's or the barbershop's data export. */
final class ExportedReviewData extends Data
{
    public function __construct(
        public readonly int $booking_request_id,
        public readonly int $rating,
        public readonly ?string $comment,
        public readonly string $published_at,
        public readonly ?string $hidden_at,
    ) {}
}

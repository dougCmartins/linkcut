<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** The fields of a new review. The caller already cleaned the comment and the first name. */
final class CreateReviewData extends Data
{
    public function __construct(
        public readonly int $client_id,
        public readonly int $booking_request_id,
        public readonly int $rating,
        public readonly ?string $comment,
        public readonly string $author_first_name,
    ) {}
}

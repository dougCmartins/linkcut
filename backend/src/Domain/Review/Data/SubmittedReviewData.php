<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** Stored review, returned to the client who just sent it. */
final class SubmittedReviewData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $rating,
    ) {}
}

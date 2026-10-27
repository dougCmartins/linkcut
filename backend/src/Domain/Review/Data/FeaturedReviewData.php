<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** One comment on the portfolio carousel. No photo. */
final class FeaturedReviewData extends Data
{
    public function __construct(
        public readonly string $first_name,
        public readonly int $rating,
        public readonly string $comment,
    ) {}
}

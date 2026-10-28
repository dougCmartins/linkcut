<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** Whether the public page may read the average and the featured comments. */
final class ReviewVisibilityData extends Data
{
    public function __construct(
        public readonly bool $show_rating,
        public readonly bool $show_reviews,
    ) {}
}

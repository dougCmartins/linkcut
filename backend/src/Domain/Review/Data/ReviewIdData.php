<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

final class ReviewIdData extends Data
{
    public function __construct(public readonly int $review_id) {}
}

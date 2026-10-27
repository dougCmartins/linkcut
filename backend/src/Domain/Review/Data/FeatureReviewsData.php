<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** The ids the barber wants on the page, in the order they should appear. At most three. */
final class FeatureReviewsData extends Data
{
    /** @param list<int> $review_ids */
    public function __construct(public readonly array $review_ids) {}
}

<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

/** Booking ids of the signed-in client, used to mark which visits already have a review. */
final class BookingIdsData extends Data
{
    /** @param list<int> $ids */
    public function __construct(public readonly array $ids) {}
}

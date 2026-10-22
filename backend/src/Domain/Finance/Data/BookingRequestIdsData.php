<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class BookingRequestIdsData extends Data
{
    /** @param list<int> $ids */
    public function __construct(public readonly array $ids) {}
}

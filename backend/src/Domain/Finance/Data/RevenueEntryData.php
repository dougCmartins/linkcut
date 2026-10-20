<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** One line of the history. */
final class RevenueEntryData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $date,
        public readonly string $time,
        public readonly string $client_name,
        public readonly string $service_name,
        public readonly int $price_in_cents,
        public readonly int $cost_in_cents,
    ) {}
}

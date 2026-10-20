<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** A taken time (no client data): used to compute the free times. */
final class BusySlotData extends Data
{
    public function __construct(
        public readonly string $date,
        public readonly string $starts_at,
        public readonly string $ends_at,
    ) {}
}

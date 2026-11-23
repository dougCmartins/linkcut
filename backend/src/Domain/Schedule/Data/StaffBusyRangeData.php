<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** Busy slots for one barber in a date range. */
final class StaffBusyRangeData extends Data
{
    public function __construct(
        public readonly int $staff_id,
        public readonly string $from,
        public readonly string $to,
    ) {}
}

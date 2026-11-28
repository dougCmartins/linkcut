<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** The bookings of one barber of the current unit. */
final class StaffBookingsQueryData extends Data
{
    public function __construct(public readonly int $staff_id) {}
}

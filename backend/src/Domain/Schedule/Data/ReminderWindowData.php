<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** Appointments starting from `from` until `to` (local "Y-m-d H:i" of the barbershop). */
final class ReminderWindowData extends Data
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {}
}

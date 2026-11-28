<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** A booking still to happen (pending or confirmed, today or later). */
final class UpcomingBookingData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $date,
        public readonly string $starts_at,
        public readonly string $client_name,
    ) {}
}

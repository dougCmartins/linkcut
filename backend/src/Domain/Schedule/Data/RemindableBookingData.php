<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** A booking due for its reminder, with the barbershop it belongs to. */
final class RemindableBookingData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly BookingRequestData $booking,
    ) {}
}

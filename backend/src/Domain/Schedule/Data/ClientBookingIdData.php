<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** A booking looked up for the client who made it (any other id answers 404). */
final class ClientBookingIdData extends Data
{
    public function __construct(
        public readonly int $booking_id,
        public readonly int $client_id,
    ) {}
}

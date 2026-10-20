<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Data;

use Spatie\LaravelData\Data;

/** Answer to the client: what was requested (waiting for the barber). */
final class BookingConfirmationData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $service_name,
        public readonly string $date,
        public readonly string $time,
        public readonly string $status,
    ) {}
}

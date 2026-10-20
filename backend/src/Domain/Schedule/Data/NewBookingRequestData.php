<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Spatie\LaravelData\Data;

/** A request already checked by the public booking flow (service copied, phone as digits). */
final class NewBookingRequestData extends Data
{
    public function __construct(
        public readonly string $client_name,
        public readonly string $client_whatsapp,
        public readonly int $service_id,
        public readonly string $service_name,
        public readonly int $duration_minutes,
        public readonly string $date,
        public readonly string $starts_at,
    ) {}
}

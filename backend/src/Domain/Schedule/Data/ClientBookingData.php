<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Domain\Schedule\Enums\BookingStatus;
use Spatie\LaravelData\Data;

/** A booking of a client in any barbershop (the client area), with the barbershop it belongs to. */
final class ClientBookingData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenant_id,
        public readonly string $service_name,
        public readonly string $date,
        public readonly string $starts_at,
        public readonly string $ends_at,
        public readonly BookingStatus $status,
    ) {}
}

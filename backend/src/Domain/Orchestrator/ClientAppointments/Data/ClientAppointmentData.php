<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Data;

use Domain\Orchestrator\Barbershop\Data\BarbershopContactData;
use Domain\Schedule\Enums\BookingStatus;
use Spatie\LaravelData\Data;

/** One booking in the client area, with the barbershop it is at. */
final class ClientAppointmentData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $service_name,
        public readonly string $date,
        public readonly string $starts_at,
        public readonly string $ends_at,
        public readonly BookingStatus $status,
        public readonly BarbershopContactData $barbershop,
    ) {}
}

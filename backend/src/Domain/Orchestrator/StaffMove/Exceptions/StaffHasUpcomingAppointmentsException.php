<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Exceptions;

use Domain\Schedule\Data\UpcomingBookingData;
use DomainException;

/** The barber still has bookings to attend where they leave: the owner passes each one to another barber first. */
final class StaffHasUpcomingAppointmentsException extends DomainException
{
    /** @param list<UpcomingBookingData> $appointments */
    public function __construct(private readonly array $appointments)
    {
        parent::__construct('The barber still has upcoming appointments at this unit.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_HAS_UPCOMING_APPOINTMENTS';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }

    /** @return array{appointments: list<array<string, mixed>>} */
    public function getErrorData(): array
    {
        return ['appointments' => array_map(fn (UpcomingBookingData $booking): array => $booking->toArray(), $this->appointments)];
    }
}

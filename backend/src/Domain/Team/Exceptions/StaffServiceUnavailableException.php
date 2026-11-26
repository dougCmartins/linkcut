<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** The barber does not do every service of the booking (e.g. passing an appointment to them). */
final class StaffServiceUnavailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This barber does not do every service of the appointment.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_SERVICE_UNAVAILABLE';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Schedule\Exceptions;

use DomainException;

final class BookingForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot change this appointment.');
    }

    public function getErrorCode(): string
    {
        return 'BOOKING_FORBIDDEN';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

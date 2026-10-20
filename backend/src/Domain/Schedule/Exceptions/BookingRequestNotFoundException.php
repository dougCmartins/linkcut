<?php

declare(strict_types=1);

namespace Domain\Schedule\Exceptions;

use DomainException;

/** The booking request does not exist for this tenant. */
final class BookingRequestNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Booking request not found.');
    }

    public function getErrorCode(): string
    {
        return 'BOOKING_REQUEST_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Schedule\Exceptions;

use DomainException;

/** Too close to the time to cancel from the app: the client talks to the barbershop instead. */
final class CancellationTooLateException extends DomainException
{
    public function __construct()
    {
        parent::__construct('It is too late to cancel this appointment from the app.');
    }

    public function getErrorCode(): string
    {
        return 'CANCELLATION_TOO_LATE';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

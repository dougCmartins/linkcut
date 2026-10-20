<?php

declare(strict_types=1);

namespace Domain\Schedule\Exceptions;

use DomainException;

/** The time was taken (or is outside the hours) by the time the request arrived. */
final class SlotUnavailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This time is no longer available.');
    }

    public function getErrorCode(): string
    {
        return 'SLOT_UNAVAILABLE';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

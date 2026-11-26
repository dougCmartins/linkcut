<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** Only an active barber with a login moves (an invited one is invited straight at the other unit). */
final class StaffNotMovableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This barber cannot be moved.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_NOT_MOVABLE';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

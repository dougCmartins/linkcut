<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** A barber the owner turned off (or not in any team): the dashboard is closed to them. */
final class StaffInactiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This barber is turned off in the team.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_INACTIVE';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

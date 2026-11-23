<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

final class StaffForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot access this staff member.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_FORBIDDEN';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

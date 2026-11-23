<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

final class StaffNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Staff member not found.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

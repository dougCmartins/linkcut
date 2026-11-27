<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** No such active loan in this brand. */
final class StaffMoveNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Loan not found.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_MOVE_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

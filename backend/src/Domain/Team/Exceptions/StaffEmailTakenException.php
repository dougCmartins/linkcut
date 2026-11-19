<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** The e-mail already belongs to a barber of this unit. */
final class StaffEmailTakenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This e-mail is already in the team.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_EMAIL_TAKEN';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

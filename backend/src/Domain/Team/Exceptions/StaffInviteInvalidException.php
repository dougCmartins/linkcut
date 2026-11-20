<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

final class StaffInviteInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This invite is invalid, expired, or already used.');
    }

    public function getErrorCode(): string
    {
        return 'STAFF_INVITE_INVALID';
    }

    public function getHttpStatus(): int
    {
        return 410;
    }
}

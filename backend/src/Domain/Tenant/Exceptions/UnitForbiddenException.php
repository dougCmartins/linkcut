<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** The requested unit does not belong to the signed-in user's organization. */
final class UnitForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This unit is not available for your account.');
    }

    public function getErrorCode(): string
    {
        return 'UNIT_FORBIDDEN';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

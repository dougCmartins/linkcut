<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** The unit asked for (X-Unit) is not one this user may open: another organization, or not the owner. */
final class UnitForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This unit is not available to you.');
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

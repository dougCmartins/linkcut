<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** The owner cannot turn their own barber off (the unit would lose its owner's agenda). */
final class CannotDeactivateSelfException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot turn yourself off.');
    }

    public function getErrorCode(): string
    {
        return 'CANNOT_DEACTIVATE_SELF';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

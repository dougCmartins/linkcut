<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

final class CannotDeactivateSelfException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot deactivate your own staff profile.');
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

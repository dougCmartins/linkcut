<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

final class OwnerRequiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only the account owner can manage the team.');
    }

    public function getErrorCode(): string
    {
        return 'OWNER_REQUIRED';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

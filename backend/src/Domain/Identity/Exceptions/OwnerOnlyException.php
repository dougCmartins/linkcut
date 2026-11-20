<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** A barber of the team asked for something only the owner sees or changes (finance, settings, team…). */
final class OwnerOnlyException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only the owner can do this.');
    }

    public function getErrorCode(): string
    {
        return 'OWNER_ONLY';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/** Same code as the barbers' sign-in, so the SPA reads both the same way. */
final class ClientCodeExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The code expired. Ask for a new one.');
    }

    public function getErrorCode(): string
    {
        return 'CODE_EXPIRED';
    }

    public function getHttpStatus(): int
    {
        return 410;
    }
}

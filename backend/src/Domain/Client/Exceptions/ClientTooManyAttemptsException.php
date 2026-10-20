<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/** Same code as the barbers' sign-in, so the SPA reads both the same way. */
final class ClientTooManyAttemptsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Too many wrong codes. Ask for a new one.');
    }

    public function getErrorCode(): string
    {
        return 'TOO_MANY_ATTEMPTS';
    }

    public function getHttpStatus(): int
    {
        return 429;
    }
}

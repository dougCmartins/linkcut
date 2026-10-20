<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/** Same code as the barbers' sign-in, so the SPA reads both the same way. */
final class ClientInvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Wrong e-mail or password.');
    }

    public function getErrorCode(): string
    {
        return 'INVALID_CREDENTIALS';
    }

    public function getHttpStatus(): int
    {
        return 401;
    }
}

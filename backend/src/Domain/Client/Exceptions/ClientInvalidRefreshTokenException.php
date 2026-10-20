<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/** Same code as the barbers' sign-in, so the SPA reads both the same way. */
final class ClientInvalidRefreshTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The session ended. Sign in again.');
    }

    public function getErrorCode(): string
    {
        return 'INVALID_REFRESH_TOKEN';
    }

    public function getHttpStatus(): int
    {
        return 401;
    }
}

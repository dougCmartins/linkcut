<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** Unknown, expired, revoked or reused refresh token. */
final class InvalidRefreshTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Your session expired. Please sign in again.');
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

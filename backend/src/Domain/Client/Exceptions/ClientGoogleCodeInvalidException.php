<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/** The one-time Google code is missing, already used, or older than a minute. */
final class ClientGoogleCodeInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Google sign-in code is invalid or expired.');
    }

    public function getErrorCode(): string
    {
        return 'GOOGLE_CODE_INVALID';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** The challenge is locked after the maximum wrong attempts. */
final class TooManyAttemptsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Too many wrong codes. Please sign in again.');
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

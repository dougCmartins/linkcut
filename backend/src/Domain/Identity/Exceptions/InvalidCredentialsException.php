<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** Wrong e-mail or password (never says which). */
final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
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

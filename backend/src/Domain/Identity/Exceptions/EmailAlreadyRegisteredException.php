<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** Sign-up with an e-mail that already has an account (only told after the code proved the e-mail). */
final class EmailAlreadyRegisteredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('E-mail already registered.');
    }

    public function getErrorCode(): string
    {
        return 'EMAIL_ALREADY_REGISTERED';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

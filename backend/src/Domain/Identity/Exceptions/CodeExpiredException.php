<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** The last code sent is past its time. */
final class CodeExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This code expired. Ask for a new one.');
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

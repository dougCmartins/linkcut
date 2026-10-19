<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** A new code was asked before resend_available_at. */
final class ResendTooSoonException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Wait a few seconds before asking for another code.');
    }

    public function getErrorCode(): string
    {
        return 'RESEND_TOO_SOON';
    }

    public function getHttpStatus(): int
    {
        return 429;
    }
}

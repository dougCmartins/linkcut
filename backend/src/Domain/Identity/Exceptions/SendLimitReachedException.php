<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** The challenge already sent the maximum number of codes. */
final class SendLimitReachedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Too many codes were sent. Please sign in again.');
    }

    public function getErrorCode(): string
    {
        return 'SEND_LIMIT_REACHED';
    }

    public function getHttpStatus(): int
    {
        return 429;
    }
}

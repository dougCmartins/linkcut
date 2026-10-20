<?php

declare(strict_types=1);

namespace Domain\Schedule\Exceptions;

use DomainException;

/** E.g. accepting a request that was already rejected, or concluding one that is not confirmed for today. */
final class InvalidStatusTransitionException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This change is not allowed for the current status.');
    }

    public function getErrorCode(): string
    {
        return 'INVALID_STATUS_TRANSITION';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

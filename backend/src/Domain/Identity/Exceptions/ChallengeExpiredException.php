<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** The sign-in challenge is unknown, used or past its time. */
final class ChallengeExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This sign-in expired. Please sign in again.');
    }

    public function getErrorCode(): string
    {
        return 'CHALLENGE_EXPIRED';
    }

    public function getHttpStatus(): int
    {
        return 410;
    }
}

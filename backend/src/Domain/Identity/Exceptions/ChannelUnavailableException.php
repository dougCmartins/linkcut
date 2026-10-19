<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** SMS was chosen but the account has no phone. */
final class ChannelUnavailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This channel is not available for this account.');
    }

    public function getErrorCode(): string
    {
        return 'CHANNEL_UNAVAILABLE';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

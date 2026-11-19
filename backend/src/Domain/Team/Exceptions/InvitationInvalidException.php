<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** An invitation link that is unknown, already used or expired (they all look the same). */
final class InvitationInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The invitation link is invalid, used or expired.');
    }

    public function getErrorCode(): string
    {
        return 'INVITATION_INVALID';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

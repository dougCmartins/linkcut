<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/**
 * Google cancelled, the e-mail is not verified, or this e-mail is already linked to another Google account.
 * The callback turns it into a redirect; it is not the answer of the exchange.
 */
final class ClientGoogleSignInRejectedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Google sign-in was rejected.');
    }

    public function getErrorCode(): string
    {
        return 'GOOGLE_SIGN_IN_REJECTED';
    }

    public function getHttpStatus(): int
    {
        return 401;
    }
}

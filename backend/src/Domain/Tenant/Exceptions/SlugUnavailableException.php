<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** The link is taken or reserved for a system page. */
final class SlugUnavailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Slug unavailable.');
    }

    public function getErrorCode(): string
    {
        return 'SLUG_UNAVAILABLE';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

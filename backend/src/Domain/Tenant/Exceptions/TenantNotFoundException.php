<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** No tenant with this id or slug. */
final class TenantNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Tenant not found.');
    }

    public function getErrorCode(): string
    {
        return 'TENANT_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

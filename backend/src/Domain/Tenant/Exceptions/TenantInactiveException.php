<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** The tenant exists but was deactivated. */
final class TenantInactiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This account is inactive.');
    }

    public function getErrorCode(): string
    {
        return 'TENANT_INACTIVE';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

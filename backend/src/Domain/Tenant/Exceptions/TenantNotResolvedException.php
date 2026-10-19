<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** Tenant-owned data was accessed without a tenant (fail closed). */
final class TenantNotResolvedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('No tenant resolved for this request.');
    }

    public function getErrorCode(): string
    {
        return 'TENANT_NOT_RESOLVED';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

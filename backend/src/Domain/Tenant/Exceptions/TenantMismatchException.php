<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** A row was written with a tenant_id different from the current tenant. */
final class TenantMismatchException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This record belongs to another tenant.');
    }

    public function getErrorCode(): string
    {
        return 'TENANT_MISMATCH';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

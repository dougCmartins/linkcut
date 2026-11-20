<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** Only the owner of the organization manages its units. */
final class OrganizationOwnerOnlyException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only the owner of the organization can do this.');
    }

    public function getErrorCode(): string
    {
        return 'ORGANIZATION_OWNER_ONLY';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

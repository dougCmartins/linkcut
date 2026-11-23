<?php

declare(strict_types=1);

namespace Domain\Tenant\Exceptions;

use DomainException;

/** No organization (brand) with this slug. */
final class OrganizationNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Brand not found.');
    }

    public function getErrorCode(): string
    {
        return 'BRAND_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

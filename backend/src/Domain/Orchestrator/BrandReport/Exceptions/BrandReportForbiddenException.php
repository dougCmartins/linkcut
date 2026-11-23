<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Exceptions;

use DomainException;

final class BrandReportForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only the owner of the brand can see the brand reports.');
    }

    public function getErrorCode(): string
    {
        return 'BRAND_REPORT_FORBIDDEN';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

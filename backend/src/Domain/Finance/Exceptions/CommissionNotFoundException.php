<?php

declare(strict_types=1);

namespace Domain\Finance\Exceptions;

use DomainException;

final class CommissionNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Commission record not found.');
    }

    public function getErrorCode(): string
    {
        return 'COMMISSION_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

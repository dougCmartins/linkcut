<?php

declare(strict_types=1);

namespace Domain\Finance\Exceptions;

use DomainException;

final class CommissionForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot view this commission report.');
    }

    public function getErrorCode(): string
    {
        return 'COMMISSION_FORBIDDEN';
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}

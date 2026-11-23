<?php

declare(strict_types=1);

namespace Domain\Finance\Exceptions;

use DomainException;

final class PeriodClosedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This appointment belongs to a closed commission period.');
    }

    public function getErrorCode(): string
    {
        return 'COMMISSION_PERIOD_CLOSED';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

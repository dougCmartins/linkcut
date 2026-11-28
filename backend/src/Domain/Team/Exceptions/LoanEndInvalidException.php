<?php

declare(strict_types=1);

namespace Domain\Team\Exceptions;

use DomainException;

/** A loan ends after it starts, and an extension moves the end later (never earlier). */
final class LoanEndInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Pick a later end date for the loan.');
    }

    public function getErrorCode(): string
    {
        return 'LOAN_END_INVALID';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

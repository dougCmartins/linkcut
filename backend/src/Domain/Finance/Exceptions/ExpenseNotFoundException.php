<?php

declare(strict_types=1);

namespace Domain\Finance\Exceptions;

use DomainException;

/** The expense does not exist for this tenant (or was already removed). */
final class ExpenseNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Expense not found.');
    }

    public function getErrorCode(): string
    {
        return 'EXPENSE_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

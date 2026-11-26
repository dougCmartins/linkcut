<?php

declare(strict_types=1);

namespace Domain\Team\Enums;

enum StaffMoveKind: string
{
    /** The barber now works at the other unit, with no date to come back. */
    case Transfer = 'transfer';
    /** The barber works at the other unit until a date, then comes back. */
    case Loan = 'loan';
}

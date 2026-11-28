<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\ActiveLoanData;
use Domain\Team\Data\StaffMoveData;
use Domain\Team\Exceptions\LoanEndInvalidException;
use Domain\Team\Models\StaffMove;
use Domain\Team\Support\StaffMoveMapper;

/** "Estender": the loan ends later (a date after today and after the current end). */
final class ExtendLoan
{
    public function handle(ActiveLoanData $loan, string $endsOn, string $today): StaffMoveData
    {
        if ($endsOn <= $today || $endsOn <= $loan->ends_on) {
            throw new LoanEndInvalidException;
        }
        $move = StaffMove::query()->findOrFail($loan->id);
        $move->update(['ends_on' => $endsOn]);

        return StaffMoveMapper::toData($move->refresh());
    }
}

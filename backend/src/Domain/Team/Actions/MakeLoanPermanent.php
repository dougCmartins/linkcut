<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\ActiveLoanData;
use Domain\Team\Data\StaffMoveData;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Models\StaffMove;
use Domain\Team\Support\StaffMoveMapper;

/** "Tornar definitivo": the loan becomes a transfer (no end date); the barber stays where they are. */
final class MakeLoanPermanent
{
    public function handle(ActiveLoanData $loan, int $byUserId): StaffMoveData
    {
        $move = StaffMove::query()->findOrFail($loan->id);
        $move->update(['ends_on' => null, 'status' => StaffMoveStatus::MadePermanent, 'ended_at' => now(), 'ended_by' => $byUserId]);

        return StaffMoveMapper::toData($move->refresh());
    }
}

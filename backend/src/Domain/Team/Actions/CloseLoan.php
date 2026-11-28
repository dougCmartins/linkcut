<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\ActiveLoanData;
use Domain\Team\Data\StaffMoveData;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffMove;
use Domain\Team\Support\StaffMoveMapper;

/**
 * The barber comes back from the loan (Team side): the row at the origin is on again, the one at the destination off
 * (never deleted), and the move is ended. Rows of two units: changed by id, on purpose, without the tenant scope.
 */
final class CloseLoan
{
    public function handle(ActiveLoanData $loan, ?int $byUserId): StaffMoveData
    {
        Staff::withoutTenantScope()->whereKey($loan->from_staff_id)->update(['active' => true]);
        Staff::withoutTenantScope()->whereKey($loan->to_staff_id)->update(['active' => false]);
        $move = StaffMove::query()->findOrFail($loan->id);
        $move->update(['status' => StaffMoveStatus::Ended, 'ended_at' => now(), 'ended_by' => $byUserId]);

        return StaffMoveMapper::toData($move->refresh());
    }
}

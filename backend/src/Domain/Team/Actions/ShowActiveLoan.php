<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\ActiveLoanData;
use Domain\Team\Data\StaffMoveIdData;
use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Team\Exceptions\StaffMoveNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffMove;

/** An active loan of the brand, with the unit of each side. Anything else (ended, transfer, other brand): 404. */
final class ShowActiveLoan
{
    public function handle(StaffMoveIdData $data): ActiveLoanData
    {
        $move = StaffMove::query()
            ->whereKey($data->id)
            ->where('organization_id', $data->organization_id)
            ->where('kind', StaffMoveKind::Loan)
            ->where('status', StaffMoveStatus::Active)
            ->first() ?? throw new StaffMoveNotFoundException;
        $tenants = Staff::withoutTenantScope()->whereKey([$move->from_staff_id, $move->to_staff_id])->pluck('tenant_id', 'id');

        return new ActiveLoanData(
            id: $move->id,
            user_id: (int) $move->user_id,
            from_staff_id: (int) $move->from_staff_id,
            from_tenant_id: (int) $tenants[$move->from_staff_id],
            to_staff_id: (int) $move->to_staff_id,
            to_tenant_id: (int) $tenants[$move->to_staff_id],
            ends_on: (string) $move->ends_on?->toDateString(),
        );
    }
}

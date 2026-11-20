<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Models\Staff;

/** Used by the HTTP money-redaction middleware (outside Domain imports). */
final class FindStaffPermissionsForUser
{
    public function handle(int $userId): ?StaffPermissionsData
    {
        $staff = Staff::withoutTenantScope()
            ->with('permissions')
            ->where('user_id', $userId)
            ->first();
        if (! $staff?->permissions) {
            return null;
        }

        return new StaffPermissionsData(
            see_prices: (bool) $staff->permissions->see_prices,
            see_own_commission: (bool) $staff->permissions->see_own_commission,
            see_unit_agenda: (bool) $staff->permissions->see_unit_agenda,
        );
    }
}

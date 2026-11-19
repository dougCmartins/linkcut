<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;

/** What a signed-in barber may see. No active barber row: nothing (fail closed). */
final class ShowPermissionsOfUser
{
    public function handle(int $userId): StaffPermissionsData
    {
        $staffId = Staff::withoutTenantScope()->where('user_id', $userId)->where('active', true)->value('id');
        $permissions = $staffId ? StaffPermission::withoutTenantScope()->where('staff_id', $staffId)->first() : null;

        return new StaffPermissionsData(
            see_prices: (bool) $permissions?->see_prices,
            see_own_commission: (bool) $permissions?->see_own_commission,
            see_unit_agenda: (bool) $permissions?->see_unit_agenda,
        );
    }
}

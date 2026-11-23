<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Models\Staff;

final class StaffDataMapper
{
    public static function toData(Staff $staff): StaffData
    {
        $permissions = $staff->permissions;

        return new StaffData(
            id: $staff->id,
            tenant_id: (int) $staff->tenant_id,
            user_id: $staff->user_id !== null ? (int) $staff->user_id : null,
            name: $staff->name,
            avatar: $staff->avatar,
            active: (bool) $staff->active,
            pending_invite: $staff->user_id === null,
            permissions: new StaffPermissionsData(
                see_prices: (bool) ($permissions?->see_prices ?? false),
                see_own_commission: (bool) ($permissions?->see_own_commission ?? false),
                see_unit_agenda: (bool) ($permissions?->see_unit_agenda ?? false),
            ),
        );
    }
}

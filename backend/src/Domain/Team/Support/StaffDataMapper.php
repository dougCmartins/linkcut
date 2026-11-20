<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Models\Staff;

/** One place that turns a Staff row into what the API returns. */
final class StaffDataMapper
{
    public static function toData(Staff $staff, int $actingUserId): StaffData
    {
        $permissions = $staff->permissions;

        return new StaffData(
            id: $staff->id,
            name: $staff->name,
            email: $staff->email,
            status: match (true) {
                ! $staff->active => 'inactive',
                $staff->user_id === null => 'invited',
                default => 'active',
            },
            is_you: $staff->user_id === $actingUserId,
            permissions: new StaffPermissionsData(
                see_prices: (bool) $permissions?->see_prices,
                see_own_commission: (bool) $permissions?->see_own_commission,
                see_unit_agenda: (bool) $permissions?->see_unit_agenda,
            ),
        );
    }
}

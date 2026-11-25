<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\StaffPermissionsData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\StaffDataMapper;

final class UpdateStaffPermissions
{
    public function handle(StaffIdData $id, StaffPermissionsData $data): StaffData
    {
        $staff = Staff::query()->whereKey($id->id)->first();
        if (! $staff) {
            throw new StaffNotFoundException;
        }

        StaffPermission::query()->updateOrCreate(
            ['staff_id' => $staff->id],
            [
                'see_prices' => $data->see_prices,
                'see_own_commission' => $data->see_own_commission,
                'see_unit_agenda' => $data->see_unit_agenda,
            ],
        );

        return StaffDataMapper::toData($staff->fresh()->load('permissions'));
    }
}

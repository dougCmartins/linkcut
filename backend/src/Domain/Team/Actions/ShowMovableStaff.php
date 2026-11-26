<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\MovableStaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Exceptions\StaffNotMovableException;
use Domain\Team\Models\Staff;

/** A barber of the current unit, if they can move: active and with a login (not an invitation still pending). */
final class ShowMovableStaff
{
    public function handle(StaffIdData $data): MovableStaffData
    {
        $staff = Staff::query()->whereKey($data->id)->first() ?? throw new StaffNotFoundException;
        if ($staff->user_id === null || ! $staff->active) {
            throw new StaffNotMovableException;
        }

        return new MovableStaffData(
            staff_id: $staff->id,
            tenant_id: (int) $staff->tenant_id,
            user_id: (int) $staff->user_id,
            name: $staff->name,
            avatar: $staff->avatar,
        );
    }
}

<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\UpdateStaffActiveData;
use Domain\Team\Exceptions\CannotDeactivateSelfException;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;

final class UpdateStaffActive
{
    public function handle(StaffIdData $id, UpdateStaffActiveData $data, ?int $actorUserId): StaffData
    {
        $staff = Staff::query()->with('permissions')->whereKey($id->id)->first();
        if (! $staff) {
            throw new StaffNotFoundException;
        }
        if (! $data->active && $actorUserId !== null && (int) $staff->user_id === $actorUserId) {
            throw new CannotDeactivateSelfException;
        }

        $staff->update(['active' => $data->active]);

        return StaffDataMapper::toData($staff->refresh()->load('permissions'));
    }
}

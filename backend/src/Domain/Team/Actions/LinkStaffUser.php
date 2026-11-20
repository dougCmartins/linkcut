<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;

final class LinkStaffUser
{
    public function handle(StaffIdData $id, int $userId, string $name): StaffData
    {
        $staff = Staff::withoutTenantScope()->with('permissions')->whereKey($id->id)->first();
        if (! $staff) {
            throw new StaffNotFoundException;
        }

        $staff->update([
            'user_id' => $userId,
            'name' => $name,
            'active' => true,
        ]);

        return StaffDataMapper::toData($staff->refresh()->load('permissions'));
    }
}

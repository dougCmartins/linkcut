<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;

final class FindStaffByUser
{
    public function handle(int $userId): ?StaffData
    {
        $staff = Staff::withoutTenantScope()
            ->with('permissions')
            ->where('user_id', $userId)
            ->first();

        return $staff ? StaffDataMapper::toData($staff) : null;
    }
}

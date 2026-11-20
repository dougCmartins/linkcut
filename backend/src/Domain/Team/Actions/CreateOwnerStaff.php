<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\OwnerStaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;

/** Every unit has at least one barber: at sign-up, its owner (in the current tenant). */
final class CreateOwnerStaff
{
    public function handle(OwnerStaffData $data): void
    {
        $staff = Staff::query()->create([
            'user_id' => $data->user_id,
            'name' => mb_substr(trim($data->name), 0, 60),
            'email' => mb_strtolower(trim($data->email)),
            'active' => true,
        ]);
        StaffPermission::query()->create(['staff_id' => $staff->id]);
    }
}

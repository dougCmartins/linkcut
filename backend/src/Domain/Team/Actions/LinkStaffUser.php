<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffUserData;
use Domain\Team\Models\Staff;

/** The barber accepted: their login is theirs from now on (inside the barber's unit). */
final class LinkStaffUser
{
    public function handle(StaffUserData $data): void
    {
        Staff::query()->whereKey($data->staff_id)->update(['user_id' => $data->user_id]);
    }
}

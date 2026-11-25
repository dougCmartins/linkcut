<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Support\StaffDataMapper;

final class ShowStaff
{
    public function handle(StaffIdData $data): StaffData
    {
        $staff = Staff::query()->with('permissions')->whereKey($data->id)->first();
        if (! $staff) {
            throw new StaffNotFoundException;
        }

        return StaffDataMapper::toData($staff);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffIdData;
use Domain\Team\Models\Staff;

/** The barber's row at the unit they leave is turned off, never deleted (their past earnings point to it). */
final class DeactivateStaffForMove
{
    public function handle(StaffIdData $data): void
    {
        Staff::query()->whereKey($data->id)->update(['active' => false]);
    }
}

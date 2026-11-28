<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\PlaceStaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;

/**
 * Turns the barber on at the current unit (the destination of a move): their old row there when they worked here
 * before, otherwise a new one. The switches are copied from the origin or start off, as the owner chose.
 * Hours and services are set by the caller with the usual actions.
 */
final class PlaceStaffInUnit
{
    public function handle(PlaceStaffData $data): int
    {
        $staff = Staff::query()->where('user_id', $data->user_id)->first();
        if ($staff) {
            $staff->update(['active' => true]);
        } else {
            $staff = Staff::query()->create(['user_id' => $data->user_id, 'name' => $data->name, 'avatar' => $data->avatar, 'active' => true]);
        }

        $origin = $data->keep_permissions ? StaffPermission::query()->where('staff_id', $data->from_staff_id)->first() : null;
        StaffPermission::query()->updateOrCreate(['staff_id' => $staff->id], [
            'see_prices' => (bool) $origin?->see_prices,
            'see_own_commission' => (bool) $origin?->see_own_commission,
            'see_unit_agenda' => (bool) $origin?->see_unit_agenda,
        ]);

        return $staff->id;
    }
}

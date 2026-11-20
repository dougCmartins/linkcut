<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffActorData;
use Domain\Team\Data\StaffData;
use Domain\Team\Data\StaffUpdateData;
use Domain\Team\Exceptions\CannotDeactivateSelfException;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\StaffDataMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** The owner turns a barber on or off and sets their 3 switches. An inactive barber cannot sign in or accept. */
final class UpdateStaff
{
    public function handle(StaffActorData $actor, StaffUpdateData $data): StaffData
    {
        $staff = Staff::query()->find($actor->staff_id) ?? throw new StaffNotFoundException;
        if (! $data->active && $staff->user_id === $actor->user_id) {
            throw new CannotDeactivateSelfException;
        }

        DB::transaction(function () use ($staff, $data): void {
            $staff->update(['active' => $data->active]);
            StaffPermission::query()->updateOrCreate(['staff_id' => $staff->id], $data->permissions->toArray());
        });
        Log::info('team_staff_updated', ['staff_id' => $staff->id, 'active' => $data->active]);

        return StaffDataMapper::toData($staff->refresh()->load('permissions'), $actor->user_id);
    }
}

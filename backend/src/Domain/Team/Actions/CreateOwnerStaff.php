<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\NewOwnerStaffData;
use Domain\Team\Data\StaffData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\SeedStaffSchedule;
use Domain\Team\Support\StaffDataMapper;
use Domain\Tenant\Context\CurrentTenant;

/** Ensures the account owner has a staff row on their home unit. */
final class CreateOwnerStaff
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(NewOwnerStaffData $data): StaffData
    {
        return $this->currentTenant->run($data->tenant_id, function () use ($data): StaffData {
            $existing = Staff::query()->where('user_id', $data->user_id)->first();
            if ($existing) {
                return StaffDataMapper::toData($existing->loadMissing('permissions'));
            }

            $staff = Staff::query()->create([
                'user_id' => $data->user_id,
                'name' => $data->name,
                'avatar' => null,
                'active' => true,
            ]);
            StaffPermission::query()->create([
                'staff_id' => $staff->id,
                'see_prices' => false,
                'see_own_commission' => false,
                'see_unit_agenda' => false,
            ]);
            SeedStaffSchedule::forStaff($data->tenant_id, $staff->id);

            return StaffDataMapper::toData($staff->load('permissions'));
        });
    }
}

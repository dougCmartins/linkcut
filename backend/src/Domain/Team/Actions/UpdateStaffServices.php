<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\UpdateStaffServicesData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffService;
use Illuminate\Support\Facades\DB;

final class UpdateStaffServices
{
    /** @return list<int> */
    public function handle(StaffIdData $staff, UpdateStaffServicesData $data): array
    {
        $member = Staff::query()->whereKey($staff->id)->first() ?? throw new StaffNotFoundException;

        DB::transaction(function () use ($member, $data): void {
            StaffService::query()->where('staff_id', $member->id)->delete();
            foreach ($data->service_ids as $serviceId) {
                StaffService::query()->create([
                    'staff_id' => $member->id,
                    'service_id' => $serviceId,
                ]);
            }
        });

        return app(ListStaffServiceIds::class)->handle($staff);
    }
}

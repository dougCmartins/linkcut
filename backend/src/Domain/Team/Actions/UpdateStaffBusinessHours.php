<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Data\UpdateStaffHoursData;
use Domain\Team\Exceptions\StaffNotFoundException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffBusinessHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\DataCollection;

final class UpdateStaffBusinessHours
{
    /** @return DataCollection<int, StaffBusinessHourData> */
    public function handle(StaffIdData $staff, UpdateStaffHoursData $data): DataCollection
    {
        $member = Staff::query()->whereKey($staff->id)->first() ?? throw new StaffNotFoundException;
        $this->ensureValidHours($data);

        DB::transaction(function () use ($member, $data): void {
            StaffBusinessHour::query()->where('staff_id', $member->id)->delete();
            foreach ($data->weekly_hours as $hour) {
                /** @var StaffBusinessHourData $hour */
                StaffBusinessHour::query()->create([
                    'staff_id' => $member->id,
                    'weekday' => $hour->weekday,
                    'is_open' => $hour->is_open,
                    'opens_at' => $hour->opens_at,
                    'closes_at' => $hour->closes_at,
                ]);
            }
        });

        return app(ListStaffBusinessHours::class)->handle($staff);
    }

    private function ensureValidHours(UpdateStaffHoursData $data): void
    {
        $seen = [];
        foreach ($data->weekly_hours as $index => $hours) {
            /** @var StaffBusinessHourData $hours */
            if (isset($seen[$hours->weekday])) {
                throw ValidationException::withMessages(["weekly_hours.{$index}.weekday" => 'distinct']);
            }
            $seen[$hours->weekday] = true;
            if ($hours->is_open && $hours->closes_at <= $hours->opens_at) {
                throw ValidationException::withMessages(["weekly_hours.{$index}.closes_at" => 'after']);
            }
        }
    }
}

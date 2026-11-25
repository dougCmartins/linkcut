<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Models\StaffBusinessHour;
use Spatie\LaravelData\DataCollection;

final class ListStaffBusinessHours
{
    /** @return DataCollection<int, StaffBusinessHourData> */
    public function handle(StaffIdData $data): DataCollection
    {
        $hours = StaffBusinessHour::query()
            ->where('staff_id', $data->id)
            ->orderBy('weekday')
            ->get()
            ->map(fn (StaffBusinessHour $hour): StaffBusinessHourData => new StaffBusinessHourData(
                weekday: $hour->weekday,
                is_open: $hour->is_open,
                opens_at: substr((string) $hour->opens_at, 0, 5),
                closes_at: substr((string) $hour->closes_at, 0, 5),
            ));

        return StaffBusinessHourData::collect($hours, DataCollection::class);
    }
}

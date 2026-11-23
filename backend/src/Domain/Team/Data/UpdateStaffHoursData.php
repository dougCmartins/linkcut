<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class UpdateStaffHoursData extends Data
{
    /** @param DataCollection<int, StaffBusinessHourData> $weekly_hours */
    public function __construct(
        #[Required, DataCollectionOf(StaffBusinessHourData::class)]
        public readonly DataCollection $weekly_hours,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** "Agenda" tab, saved as a whole: services, the 7 weekdays, booking rules and vacation. */
final class UpdateScheduleSettingsData extends Data
{
    /**
     * @param  DataCollection<int, ServiceInputData>  $services
     * @param  DataCollection<int, BusinessHourData>  $weekly_hours
     */
    public function __construct(
        #[DataCollectionOf(ServiceInputData::class), Min(1), Max(20)]
        public readonly DataCollection $services,
        #[DataCollectionOf(BusinessHourData::class), Size(7)]
        public readonly DataCollection $weekly_hours,
        #[Required, IntegerType, In([15, 30, 45, 60])]
        public readonly int $slot_interval_minutes,
        #[Required, IntegerType, In([7, 14, 21, 30])]
        public readonly int $booking_window_days,
        public readonly VacationData $vacation,
    ) {}
}

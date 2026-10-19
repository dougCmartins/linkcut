<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** "Agenda" tab as the barber sees it (services with cost). */
final class ScheduleSettingsData extends Data
{
    /**
     * @param  DataCollection<int, ServiceData>  $services
     * @param  DataCollection<int, BusinessHourData>  $weekly_hours
     */
    public function __construct(
        #[DataCollectionOf(ServiceData::class)]
        public readonly DataCollection $services,
        #[DataCollectionOf(BusinessHourData::class)]
        public readonly DataCollection $weekly_hours,
        public readonly int $slot_interval_minutes,
        public readonly int $booking_window_days,
        public readonly VacationData $vacation,
    ) {}
}

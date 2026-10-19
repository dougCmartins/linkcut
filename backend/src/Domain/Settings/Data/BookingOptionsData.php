<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** What the public booking needs: services (without cost), hours, rules and vacation. */
final class BookingOptionsData extends Data
{
    /**
     * @param  DataCollection<int, PublicServiceData>  $services
     * @param  DataCollection<int, BusinessHourData>  $weekly_hours
     */
    public function __construct(
        #[DataCollectionOf(PublicServiceData::class)]
        public readonly DataCollection $services,
        #[DataCollectionOf(BusinessHourData::class)]
        public readonly DataCollection $weekly_hours,
        public readonly int $slot_interval_minutes,
        public readonly int $booking_window_days,
        public readonly VacationData $vacation,
    ) {}
}

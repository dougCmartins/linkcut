<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Actions;

use Domain\Orchestrator\PublicBooking\Data\AvailabilityData;
use Domain\Orchestrator\PublicBooking\Data\AvailabilityQueryData;
use Domain\Orchestrator\PublicBooking\Data\AvailableDayData;
use Domain\Orchestrator\PublicBooking\Support\AvailableTimes;
use Domain\Orchestrator\PublicBooking\Support\StaffAvailability;
use Domain\Schedule\Actions\ListBusySlots;
use Domain\Schedule\Data\BusySlotData;
use Domain\Schedule\Data\DateRangeData;
use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Data\BusinessHourData;
use Domain\Team\Actions\ListActiveStaffForServices;
use Domain\Team\Actions\ListStaffBlockRangesForDate;
use Domain\Team\Actions\ListStaffBusinessHours;
use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Tenant\Context\TenantClock;
use Spatie\LaravelData\DataCollection;

/** Free times of the booking window: staff hours + blocks + taken times per barber (or any free barber). */
final class ShowAvailability
{
    public function __construct(
        private readonly ShowBookingOptions $showBookingOptions,
        private readonly ListBusySlots $listBusySlots,
        private readonly ListActiveStaffForServices $listActiveStaff,
        private readonly ListStaffBusinessHours $listHours,
        private readonly ListStaffBlockRangesForDate $listBlocks,
        private readonly TenantClock $clock,
    ) {}

    public function handle(AvailabilityQueryData $data): AvailabilityData
    {
        $options = $this->showBookingOptions->handle();
        $serviceIds = array_map('intval', $data->service_ids ?? []);
        $service = AvailableTimes::services($options, $serviceIds);

        $staffIds = $data->staff_id !== null
            ? [$data->staff_id]
            : $this->listActiveStaff->handle($serviceIds !== [] ? $serviceIds : [$service->id]);

        $now = $this->clock->now();
        $today = $now->startOfDay();
        $last = $today->addDays($options->booking_window_days - 1);

        $days = [];
        for ($day = $today; $day <= $last; $day = $day->addDay()) {
            $date = $day->toDateString();
            $times = [];
            foreach ($staffIds as $staffId) {
                $hours = $this->staffHoursForDay($staffId, $day->dayOfWeek);
                $busy = collect($this->listBusySlots->handle(new DateRangeData(from: $date, to: $date), $staffId)->all())
                    ->map(fn (BusySlotData $slot): array => [$slot->starts_at, $slot->ends_at])
                    ->values()
                    ->all();
                $blocks = $this->listBlocks->handle(new StaffIdData(id: $staffId), $date);
                $staffTimes = StaffAvailability::forDay(
                    date: $date,
                    hours: $hours,
                    vacation: $options->vacation,
                    intervalMinutes: $options->slot_interval_minutes,
                    durationMinutes: $service->duration_minutes,
                    busy: array_merge($busy, $blocks),
                    nowMinutes: $day->isSameDay($now) ? $now->hour * 60 + $now->minute : null,
                );
                $times = array_values(array_unique([...$times, ...$staffTimes]));
            }
            sort($times);
            $days[] = new AvailableDayData(date: $date, times: $times);
        }

        return new AvailabilityData(service_id: $service->id, days: new DataCollection(AvailableDayData::class, $days));
    }

    private function staffHoursForDay(int $staffId, int $weekday): ?BusinessHourData
    {
        $hours = $this->listHours->handle(new StaffIdData(id: $staffId));
        /** @var StaffBusinessHourData|null $match */
        $match = collect($hours->all())->first(fn (StaffBusinessHourData $item): bool => $item->weekday === $weekday);
        if ($match === null) {
            return null;
        }

        return new BusinessHourData(
            weekday: $match->weekday,
            is_open: $match->is_open,
            opens_at: $match->opens_at,
            closes_at: $match->closes_at,
        );
    }
}

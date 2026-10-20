<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Actions;

use Domain\Orchestrator\PublicBooking\Data\AvailabilityData;
use Domain\Orchestrator\PublicBooking\Data\AvailabilityQueryData;
use Domain\Orchestrator\PublicBooking\Data\AvailableDayData;
use Domain\Orchestrator\PublicBooking\Support\AvailableTimes;
use Domain\Schedule\Actions\ListBusySlots;
use Domain\Schedule\Data\BusySlotData;
use Domain\Schedule\Data\DateRangeData;
use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\PublicServiceData;
use Domain\Tenant\Context\TenantClock;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\DataCollection;

/** Free times of the booking window: rules from Settings, taken times from Schedule. */
final class ShowAvailability
{
    public function __construct(
        private readonly ShowBookingOptions $showBookingOptions,
        private readonly ListBusySlots $listBusySlots,
        private readonly TenantClock $clock,
    ) {}

    public function handle(AvailabilityQueryData $data): AvailabilityData
    {
        $options = $this->showBookingOptions->handle();
        $service = $this->service($options->services->all(), $data->service_id);

        $now = $this->clock->now();
        $today = $now->startOfDay();
        $last = $today->addDays($options->booking_window_days - 1);
        $busy = collect($this->listBusySlots->handle(new DateRangeData(from: $today->toDateString(), to: $last->toDateString()))->all())
            ->groupBy(fn (BusySlotData $slot): string => $slot->date);
        $hoursByWeekday = collect($options->weekly_hours->all())->keyBy(fn (BusinessHourData $hours): int => $hours->weekday);

        $days = [];
        for ($day = $today; $day <= $last; $day = $day->addDay()) {
            $date = $day->toDateString();
            $days[] = new AvailableDayData(date: $date, times: AvailableTimes::forDay(
                date: $date,
                hours: $hoursByWeekday->get($day->dayOfWeek),
                vacation: $options->vacation,
                intervalMinutes: $options->slot_interval_minutes,
                durationMinutes: $service->duration_minutes,
                busy: $busy->get($date, collect())->map(fn (BusySlotData $slot): array => [$slot->starts_at, $slot->ends_at])->values()->all(),
                nowMinutes: $day->isSameDay($now) ? $now->hour * 60 + $now->minute : null,
            ));
        }

        return new AvailabilityData(service_id: $service->id, days: new DataCollection(AvailableDayData::class, $days));
    }

    /** @param list<PublicServiceData> $services */
    private function service(array $services, ?int $serviceId): PublicServiceData
    {
        $service = $serviceId
            ? collect($services)->first(fn (PublicServiceData $item): bool => $item->id === $serviceId)
            : collect($services)->sortBy('duration_minutes')->first();

        return $service ?? throw ValidationException::withMessages(['service_id' => 'exists']);
    }
}

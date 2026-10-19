<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\ScheduleSettingsData;
use Domain\Settings\Data\ServiceData;
use Domain\Settings\Data\VacationData;
use Domain\Settings\Models\BookingSettings;
use Domain\Settings\Models\BusinessHour;
use Domain\Settings\Models\Service;
use Spatie\LaravelData\DataCollection;

/** Services (with the private cost), weekly hours, booking rules and vacation. */
final class ShowScheduleSettings
{
    public function handle(): ScheduleSettingsData
    {
        $booking = BookingSettings::query()->firstOrFail();
        $services = Service::query()->ordered()->get()
            ->map(fn (Service $service): ServiceData => new ServiceData(
                id: $service->id,
                name: $service->name,
                duration_minutes: $service->duration_minutes,
                price_in_cents: $service->price_in_cents,
                cost_in_cents: $service->cost_in_cents,
            ))
            ->all();
        $hours = BusinessHour::query()->orderBy('weekday')->get()
            ->map(fn (BusinessHour $hour): BusinessHourData => new BusinessHourData(
                weekday: $hour->weekday,
                is_open: $hour->is_open,
                opens_at: $hour->opens_at,
                closes_at: $hour->closes_at,
            ))
            ->all();

        return new ScheduleSettingsData(
            services: new DataCollection(ServiceData::class, $services),
            weekly_hours: new DataCollection(BusinessHourData::class, $hours),
            slot_interval_minutes: $booking->slot_interval_minutes,
            booking_window_days: $booking->booking_window_days,
            vacation: new VacationData(
                is_enabled: $booking->vacation_enabled,
                starts_on: $booking->vacation_starts_on?->format('Y-m-d'),
                ends_on: $booking->vacation_ends_on?->format('Y-m-d'),
                message: $booking->vacation_message,
            ),
        );
    }
}

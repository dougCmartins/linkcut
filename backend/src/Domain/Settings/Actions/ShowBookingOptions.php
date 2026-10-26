<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\BookingOptionsData;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\PublicServiceData;
use Domain\Settings\Data\ServiceData;
use Domain\Settings\Support\PublicSettingsCache;
use Spatie\LaravelData\DataCollection;

/** What the public booking needs. Services go out without the cost. */
final class ShowBookingOptions
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowScheduleSettings $showSchedule,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(): BookingOptionsData
    {
        return $this->cache->remember(PublicSettingsCache::BOOKING_OPTIONS, fn (): BookingOptionsData => $this->build());
    }

    private function build(): BookingOptionsData
    {
        $this->createDefaultSettings->handle(new DefaultSettingsData);
        $schedule = $this->showSchedule->handle();

        $services = array_map(fn (ServiceData $service): PublicServiceData => new PublicServiceData(
            id: $service->id,
            name: $service->name,
            duration_minutes: $service->duration_minutes,
            price_in_cents: $service->price_in_cents,
        ), $schedule->services->all());

        return new BookingOptionsData(
            services: new DataCollection(PublicServiceData::class, $services),
            weekly_hours: $schedule->weekly_hours,
            slot_interval_minutes: $schedule->slot_interval_minutes,
            booking_window_days: $schedule->booking_window_days,
            vacation: $schedule->vacation,
        );
    }
}

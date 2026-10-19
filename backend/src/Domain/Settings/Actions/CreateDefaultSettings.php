<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\BookingSettings;
use Domain\Settings\Models\BusinessHour;
use Domain\Settings\Models\PortfolioHighlight;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Settings\Models\SocialLink;
use Domain\Settings\Models\ThemeSettings;
use Domain\Settings\Support\SettingsDefaults;
use Illuminate\Support\Facades\DB;

/** Creates the starting settings of the current tenant. Does nothing when they already exist. */
final class CreateDefaultSettings
{
    public function handle(DefaultSettingsData $data): void
    {
        if (ProfileSettings::query()->exists()) {
            return;
        }

        DB::transaction(function () use ($data): void {
            ProfileSettings::query()->create([
                'name' => $data->name,
                'whatsapp_message' => SettingsDefaults::WHATSAPP_MESSAGE,
            ]);
            foreach (SettingsDefaults::socialNetworks() as $position => $network) {
                SocialLink::query()->create(['network' => $network, 'url' => null, 'position' => $position]);
            }

            PortfolioSettings::query()->create(['quote' => SettingsDefaults::QUOTE, 'story' => SettingsDefaults::STORY]);
            foreach (SettingsDefaults::highlights() as $position => $highlight) {
                PortfolioHighlight::query()->create($highlight + ['position' => $position]);
            }

            ThemeSettings::query()->create(SettingsDefaults::theme());

            foreach (SettingsDefaults::services() as $position => $service) {
                Service::query()->create($service + ['position' => $position]);
            }
            foreach (SettingsDefaults::weeklyHours() as $hours) {
                BusinessHour::query()->create($hours);
            }
            BookingSettings::query()->create([
                'slot_interval_minutes' => SettingsDefaults::SLOT_INTERVAL_MINUTES,
                'booking_window_days' => SettingsDefaults::BOOKING_WINDOW_DAYS,
                'vacation_message' => SettingsDefaults::VACATION_MESSAGE,
            ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** Everything the settings editor needs, in one call. */
final class SettingsData extends Data
{
    public function __construct(
        public readonly ProfileSettingsData $profile,
        public readonly PortfolioSettingsData $portfolio,
        public readonly ScheduleSettingsData $schedule,
        public readonly ThemeSettingsData $theme,
    ) {}
}

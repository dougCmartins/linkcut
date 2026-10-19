<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\SettingsData;

/** All the settings of the current tenant for the editor (created with defaults on first access). */
final class ShowSettings
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowProfileSettings $showProfile,
        private readonly ShowPortfolioSettings $showPortfolio,
        private readonly ShowScheduleSettings $showSchedule,
        private readonly ShowThemeSettings $showTheme,
    ) {}

    public function handle(): SettingsData
    {
        $this->createDefaultSettings->handle(new DefaultSettingsData);

        return new SettingsData(
            profile: $this->showProfile->handle(),
            portfolio: $this->showPortfolio->handle(),
            schedule: $this->showSchedule->handle(),
            theme: $this->showTheme->handle(),
        );
    }
}

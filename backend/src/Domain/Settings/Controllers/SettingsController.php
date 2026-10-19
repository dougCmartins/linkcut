<?php

declare(strict_types=1);

namespace Domain\Settings\Controllers;

use Domain\Settings\Actions\ShowSettings;
use Domain\Settings\Actions\UpdatePortfolioSettings;
use Domain\Settings\Actions\UpdateProfileSettings;
use Domain\Settings\Actions\UpdateScheduleSettings;
use Domain\Settings\Actions\UpdateThemeSettings;
use Domain\Settings\Actions\UploadImage;
use Domain\Settings\Data\ImageData;
use Domain\Settings\Data\PortfolioSettingsData;
use Domain\Settings\Data\ProfileSettingsData;
use Domain\Settings\Data\ScheduleSettingsData;
use Domain\Settings\Data\SettingsData;
use Domain\Settings\Data\ThemeSettingsData;
use Domain\Settings\Data\UpdatePortfolioSettingsData;
use Domain\Settings\Data\UpdateProfileSettingsData;
use Domain\Settings\Data\UpdateScheduleSettingsData;
use Domain\Settings\Data\UploadImageData;

final class SettingsController
{
    public function show(ShowSettings $action): SettingsData
    {
        return $action->handle();
    }

    public function updateProfile(UpdateProfileSettingsData $data, UpdateProfileSettings $action): ProfileSettingsData
    {
        return $action->handle($data);
    }

    public function updatePortfolio(UpdatePortfolioSettingsData $data, UpdatePortfolioSettings $action): PortfolioSettingsData
    {
        return $action->handle($data);
    }

    public function updateSchedule(UpdateScheduleSettingsData $data, UpdateScheduleSettings $action): ScheduleSettingsData
    {
        return $action->handle($data);
    }

    public function updateTheme(ThemeSettingsData $data, UpdateThemeSettings $action): ThemeSettingsData
    {
        return $action->handle($data);
    }

    public function uploadImage(UploadImageData $data, UploadImage $action): ImageData
    {
        return $action->handle($data);
    }
}

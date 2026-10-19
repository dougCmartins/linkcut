<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ThemeSettingsData;
use Domain\Settings\Models\ThemeSettings;

final class ShowThemeSettings
{
    public function handle(): ThemeSettingsData
    {
        $theme = ThemeSettings::query()->firstOrFail();

        return new ThemeSettingsData(
            palette: $theme->palette,
            mode: $theme->mode,
            button_shape: $theme->button_shape,
            button_style: $theme->button_style,
            custom_accent: $theme->custom_accent,
        );
    }
}

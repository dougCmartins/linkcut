<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\ThemeSettingsData;
use Domain\Settings\Models\ThemeSettings;
use Domain\Settings\Support\PublicSettingsCache;
use Illuminate\Support\Facades\Log;

/** Saves the "Aparência" tab. */
final class UpdateThemeSettings
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly PublicSettingsCache $cache,
        private readonly ShowThemeSettings $showTheme,
    ) {}

    public function handle(ThemeSettingsData $data): ThemeSettingsData
    {
        $this->createDefaultSettings->handle(new DefaultSettingsData);

        ThemeSettings::query()->firstOrFail()->update([
            'palette' => $data->palette,
            'mode' => $data->mode,
            'custom_accent' => $data->custom_accent ? mb_strtolower($data->custom_accent) : null,
            'button_shape' => $data->button_shape,
            'button_style' => $data->button_style,
        ]);

        $this->cache->forget();

        Log::info('settings_theme_updated');

        return $this->showTheme->handle();
    }
}

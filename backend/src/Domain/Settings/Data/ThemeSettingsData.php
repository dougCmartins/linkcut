<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Domain\Settings\Enums\ButtonShape;
use Domain\Settings\Enums\ButtonStyle;
use Domain\Settings\Enums\ThemeMode;
use Domain\Settings\Enums\ThemePalette;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** "Aparência" tab (input and output). custom_accent: "#rrggbb" or null to use the palette accent. */
final class ThemeSettingsData extends Data
{
    public function __construct(
        #[Required, Enum(ThemePalette::class)]
        public readonly ThemePalette $palette,
        #[Required, Enum(ThemeMode::class)]
        public readonly ThemeMode $mode,
        #[Required, Enum(ButtonShape::class)]
        public readonly ButtonShape $button_shape,
        #[Required, Enum(ButtonStyle::class)]
        public readonly ButtonStyle $button_style,
        #[Nullable, Regex('/^#[0-9a-fA-F]{6}$/')]
        public readonly ?string $custom_accent = null,
    ) {}
}

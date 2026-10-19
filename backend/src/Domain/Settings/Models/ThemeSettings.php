<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Settings\Enums\ButtonShape;
use Domain\Settings\Enums\ButtonStyle;
use Domain\Settings\Enums\ThemeMode;
use Domain\Settings\Enums\ThemePalette;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Look of the public pages (one row per tenant). */
final class ThemeSettings extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'palette', 'mode', 'custom_accent', 'button_shape', 'button_style'];

    protected function casts(): array
    {
        return ['palette' => ThemePalette::class, 'mode' => ThemeMode::class, 'button_shape' => ButtonShape::class, 'button_style' => ButtonStyle::class];
    }
}

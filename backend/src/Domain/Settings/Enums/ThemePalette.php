<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Color palettes of the public pages (defined in the SPA core.css). */
enum ThemePalette: string
{
    case Default = 'default';
    case Classic = 'classic';
    case Ocean = 'ocean';
    case Forest = 'forest';
    case Wine = 'wine';
    case Minimal = 'minimal';
}

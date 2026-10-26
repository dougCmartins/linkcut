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

    /** The palette's accent in light mode (core.css): readable on the white of e-mails. */
    public function lightAccent(): string
    {
        return match ($this) {
            self::Default => '#121212',
            self::Classic => '#b3261e',
            self::Ocean => '#0b6fa4',
            self::Forest => '#1f6b45',
            self::Wine => '#7d1d3f',
            self::Minimal => '#111111',
        };
    }
}

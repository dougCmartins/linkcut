<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Light or dark version of the palette. */
enum ThemeMode: string
{
    case Dark = 'dark';
    case Light = 'light';
}

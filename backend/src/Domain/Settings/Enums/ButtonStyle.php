<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Visual style of the main buttons. */
enum ButtonStyle: string
{
    case Elevated = 'elevated';
    case Fill = 'fill';
    case Outline = 'outline';
    case HardShadow = 'hardShadow';
    case SoftShadow = 'softShadow';
}

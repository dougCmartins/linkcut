<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Visual style of the main buttons (the old "elevated" 3D edge was removed: too tall). */
enum ButtonStyle: string
{
    case Fill = 'fill';
    case Outline = 'outline';
    case HardShadow = 'hardShadow';
    case SoftShadow = 'softShadow';
}

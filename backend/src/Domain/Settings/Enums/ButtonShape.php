<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Corner style of the buttons. */
enum ButtonShape: string
{
    case Square = 'square';
    case Rounded = 'rounded';
    case Pill = 'pill';
}

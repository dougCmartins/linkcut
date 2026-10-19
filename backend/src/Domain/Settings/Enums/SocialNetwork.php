<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Social networks shown on the bio link. */
enum SocialNetwork: string
{
    case Instagram = 'instagram';
    case Tiktok = 'tiktok';
    case Youtube = 'youtube';
}

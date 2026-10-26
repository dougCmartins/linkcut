<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Icons drawn by the app for a free profile link. Null means no icon. */
enum ProfileLinkIcon: string
{
    case Link = 'link';
    case Phone = 'phone';
    case Map = 'map';
}

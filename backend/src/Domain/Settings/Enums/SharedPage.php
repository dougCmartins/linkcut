<?php

declare(strict_types=1);

namespace Domain\Settings\Enums;

/** Which public page a shared link points to. */
enum SharedPage: string
{
    case BioLink = 'bio';
    case Portfolio = 'portfolio';
}

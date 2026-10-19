<?php

declare(strict_types=1);

namespace Domain\Identity\Enums;

enum TwoFactorChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
}

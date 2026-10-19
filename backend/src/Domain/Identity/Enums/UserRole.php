<?php

declare(strict_types=1);

namespace Domain\Identity\Enums;

enum UserRole: string
{
    /** Account owner (the barber who signed up). */
    case Owner = 'owner';
    /** Another barber of the same tenant. */
    case Barber = 'barber';
}

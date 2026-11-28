<?php

declare(strict_types=1);

namespace Domain\Team\Enums;

enum StaffMoveStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    /** A loan the owner turned into a transfer. */
    case MadePermanent = 'made_permanent';
}

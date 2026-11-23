<?php

declare(strict_types=1);

namespace Domain\Finance\Enums;

enum CommissionKind: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
}

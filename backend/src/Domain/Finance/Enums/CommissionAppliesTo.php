<?php

declare(strict_types=1);

namespace Domain\Finance\Enums;

enum CommissionAppliesTo: string
{
    case Price = 'price';
    case Commission = 'commission';
    case Appointment = 'appointment';
}

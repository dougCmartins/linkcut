<?php

declare(strict_types=1);

namespace Domain\Finance\Enums;

enum CommissionStage: string
{
    case Before = 'before';
    case After = 'after';
}

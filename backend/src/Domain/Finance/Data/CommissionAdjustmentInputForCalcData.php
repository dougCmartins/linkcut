<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Spatie\LaravelData\Data;

/** Adjustment fed into the pure calculator (no persistence). */
final class CommissionAdjustmentInputForCalcData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly CommissionStage $stage,
        public readonly CommissionKind $kind,
        public readonly CommissionAppliesTo $applies_to,
        public readonly int $value,
        public readonly ?string $system_key = null,
    ) {}
}

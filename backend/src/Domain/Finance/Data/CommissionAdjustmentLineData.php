<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** One applied adjustment in the snapshot (name, stage, signed cents deducted). */
final class CommissionAdjustmentLineData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $stage,
        public readonly string $kind,
        public readonly string $applies_to,
        public readonly int $amount_in_cents,
    ) {}
}

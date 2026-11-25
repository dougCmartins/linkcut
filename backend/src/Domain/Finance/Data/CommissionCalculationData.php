<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** Result of the pure commission calculator. */
final class CommissionCalculationData extends Data
{
    /**
     * @param  list<CommissionAdjustmentLineData>  $adjustments
     */
    public function __construct(
        public readonly int $base_in_cents,
        public readonly int $percent,
        public readonly int $amount_in_cents,
        public readonly array $adjustments,
    ) {}
}

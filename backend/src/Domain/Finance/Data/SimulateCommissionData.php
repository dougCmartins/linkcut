<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** What-if calculator for the rules screen (no persistence). */
final class SimulateCommissionData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(0)]
        public readonly int $price_in_cents,
        #[Required, IntegerType, Between(0, 1000)]
        public readonly int $percent,
        #[Required, IntegerType, Min(0)]
        public readonly int $cost_in_cents = 0,
        #[Required, IntegerType, Min(1)]
        public readonly int $staff_id = 1,
        public readonly string $date = '2026-01-01',
        public readonly bool $use_saved_adjustments = true,
    ) {}
}

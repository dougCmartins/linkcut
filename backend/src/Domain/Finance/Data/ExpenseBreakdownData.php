<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** What went out of the cash in the period, by origin. */
final class ExpenseBreakdownData extends Data
{
    public function __construct(
        /** Fixed expenses already due. */
        public readonly int $fixed_in_cents,
        public readonly int $one_off_in_cents,
        /** Cost per procedure of the appointments concluded in the period. */
        public readonly int $service_costs_in_cents,
        public readonly int $total_in_cents,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

/** Current month from day 1 until today (tenant timezone): what the dashboard shows. */
final class MonthToDateData extends Data
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly int $revenue_in_cents,
        public readonly ExpenseBreakdownData $expenses,
    ) {}
}

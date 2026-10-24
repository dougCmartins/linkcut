<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Every finance record of the barbershop (data export). */
final class FinanceRecordsData extends Data
{
    /**
     * @param  DataCollection<int, RevenueEntryData>  $revenue_entries
     * @param  DataCollection<int, FixedExpenseData>  $fixed_expenses
     * @param  DataCollection<int, OneOffExpenseData>  $one_off_expenses
     */
    public function __construct(
        public readonly DataCollection $revenue_entries,
        public readonly DataCollection $fixed_expenses,
        public readonly DataCollection $one_off_expenses,
    ) {}
}

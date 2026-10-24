<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\FinanceRecordsData;
use Domain\Finance\Data\FixedExpenseData;
use Domain\Finance\Data\OneOffExpenseData;
use Domain\Finance\Data\RevenueEntryData;
use Domain\Finance\Models\FixedExpense;
use Domain\Finance\Models\OneOffExpense;
use Domain\Finance\Models\RevenueEntry;
use Spatie\LaravelData\DataCollection;

/** All revenue and current expenses of the current barbershop, oldest first (data export / portability). */
final class ListFinanceRecords
{
    public function handle(): FinanceRecordsData
    {
        return new FinanceRecordsData(
            revenue_entries: RevenueEntryData::collect(RevenueEntry::query()->orderBy('date')->orderBy('time')->get(), DataCollection::class),
            fixed_expenses: FixedExpenseData::collect(FixedExpense::query()->orderBy('id')->get(), DataCollection::class),
            one_off_expenses: OneOffExpenseData::collect(OneOffExpense::query()->orderBy('date')->get(), DataCollection::class),
        );
    }
}

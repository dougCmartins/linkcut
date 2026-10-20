<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseListData;
use Domain\Finance\Data\FixedExpenseData;
use Domain\Finance\Data\OneOffExpenseData;
use Domain\Finance\Models\FixedExpense;
use Domain\Finance\Models\OneOffExpense;
use Domain\Tenant\Context\TenantClock;
use Spatie\LaravelData\DataCollection;

final class ListExpenses
{
    public function __construct(private readonly TenantClock $clock) {}

    public function handle(): ExpenseListData
    {
        $today = $this->clock->today();
        $oneOff = OneOffExpense::query()
            ->betweenDates($today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString())
            ->newestFirst()
            ->get();

        return new ExpenseListData(
            fixed: FixedExpenseData::collect(FixedExpense::query()->byDueDay()->get(), DataCollection::class),
            one_off: OneOffExpenseData::collect($oneOff, DataCollection::class),
        );
    }
}

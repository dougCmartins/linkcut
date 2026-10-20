<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseBreakdownData;
use Domain\Finance\Data\MonthToDateData;
use Domain\Finance\Models\FixedExpense;
use Domain\Finance\Models\OneOffExpense;
use Domain\Finance\Models\RevenueEntry;
use Domain\Tenant\Context\TenantClock;

/**
 * Revenue and expenses from day 1 until today: fixed expenses already due,
 * one-off ones of these days and the cost of the appointments concluded.
 */
final class ShowMonthToDate
{
    public function __construct(private readonly TenantClock $clock) {}

    public function handle(): MonthToDateData
    {
        $today = $this->clock->today();
        $from = $today->startOfMonth()->toDateString();
        $to = $today->toDateString();

        $revenue = fn () => RevenueEntry::query()->betweenDates($from, $to);
        $fixed = FixedExpense::query()->get()->filter(fn (FixedExpense $expense): bool => $expense->isDueOn($today))->sum('amount_in_cents');
        $oneOff = (int) OneOffExpense::query()->betweenDates($from, $to)->sum('amount_in_cents');
        $serviceCosts = (int) $revenue()->sum('cost_in_cents');

        return new MonthToDateData(
            from: $from,
            to: $to,
            revenue_in_cents: (int) $revenue()->sum('price_in_cents'),
            expenses: new ExpenseBreakdownData(
                fixed_in_cents: (int) $fixed,
                one_off_in_cents: $oneOff,
                service_costs_in_cents: $serviceCosts,
                total_in_cents: (int) $fixed + $oneOff + $serviceCosts,
            ),
        );
    }
}

<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseIdData;
use Domain\Finance\Exceptions\ExpenseNotFoundException;
use Domain\Finance\Models\FixedExpense;
use Illuminate\Support\Facades\Log;

/** Soft delete, so the barber can undo it. */
final class DeleteFixedExpense
{
    public function handle(ExpenseIdData $id): void
    {
        $expense = FixedExpense::query()->find($id->id) ?? throw new ExpenseNotFoundException;
        $expense->delete();
        Log::info('finance_fixed_expense_deleted', ['expense_id' => $expense->id]);
    }
}

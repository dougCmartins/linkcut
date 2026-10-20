<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseIdData;
use Domain\Finance\Data\FixedExpenseData;
use Domain\Finance\Exceptions\ExpenseNotFoundException;
use Domain\Finance\Models\FixedExpense;
use Illuminate\Support\Facades\Log;

/** Undo of a removal. Restoring one that is not removed just returns it. */
final class RestoreFixedExpense
{
    public function handle(ExpenseIdData $id): FixedExpenseData
    {
        $expense = FixedExpense::query()->withTrashed()->find($id->id) ?? throw new ExpenseNotFoundException;
        if ($expense->trashed()) {
            $expense->restore();
            Log::info('finance_fixed_expense_restored', ['expense_id' => $expense->id]);
        }

        return FixedExpenseData::from($expense);
    }
}

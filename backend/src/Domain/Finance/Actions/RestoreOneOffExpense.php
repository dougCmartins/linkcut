<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseIdData;
use Domain\Finance\Data\OneOffExpenseData;
use Domain\Finance\Exceptions\ExpenseNotFoundException;
use Domain\Finance\Models\OneOffExpense;
use Illuminate\Support\Facades\Log;

/** Undo of a removal. Restoring one that is not removed just returns it. */
final class RestoreOneOffExpense
{
    public function handle(ExpenseIdData $id): OneOffExpenseData
    {
        $expense = OneOffExpense::query()->withTrashed()->find($id->id) ?? throw new ExpenseNotFoundException;
        if ($expense->trashed()) {
            $expense->restore();
            Log::info('finance_one_off_expense_restored', ['expense_id' => $expense->id]);
        }

        return OneOffExpenseData::from($expense);
    }
}

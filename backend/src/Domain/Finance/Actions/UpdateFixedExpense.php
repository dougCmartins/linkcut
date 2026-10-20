<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseIdData;
use Domain\Finance\Data\FixedExpenseData;
use Domain\Finance\Data\FixedExpenseInputData;
use Domain\Finance\Exceptions\ExpenseNotFoundException;
use Domain\Finance\Models\FixedExpense;
use Illuminate\Support\Facades\Log;

/** Edits the name, amount and due day of a fixed expense. */
final class UpdateFixedExpense
{
    public function handle(ExpenseIdData $id, FixedExpenseInputData $data): FixedExpenseData
    {
        $expense = FixedExpense::query()->find($id->id) ?? throw new ExpenseNotFoundException;
        $expense->update([
            'name' => trim($data->name),
            'amount_in_cents' => $data->amount_in_cents,
            'due_day' => $data->due_day,
        ]);
        Log::info('finance_fixed_expense_updated', ['expense_id' => $expense->id]);

        return FixedExpenseData::from($expense);
    }
}

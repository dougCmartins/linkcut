<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\ExpenseIdData;
use Domain\Finance\Data\OneOffExpenseData;
use Domain\Finance\Data\OneOffExpenseInputData;
use Domain\Finance\Exceptions\ExpenseNotFoundException;
use Domain\Finance\Models\OneOffExpense;
use Illuminate\Support\Facades\Log;

final class UpdateOneOffExpense
{
    public function __construct(private readonly EnsureExpenseDateIsNotFuture $ensureDate) {}

    public function handle(ExpenseIdData $id, OneOffExpenseInputData $data): OneOffExpenseData
    {
        $expense = OneOffExpense::query()->find($id->id) ?? throw new ExpenseNotFoundException;
        $this->ensureDate->handle($data);
        $expense->update([
            'description' => trim($data->description),
            'amount_in_cents' => $data->amount_in_cents,
            'date' => $data->date,
        ]);
        Log::info('finance_one_off_expense_updated', ['expense_id' => $expense->id]);

        return OneOffExpenseData::from($expense);
    }
}

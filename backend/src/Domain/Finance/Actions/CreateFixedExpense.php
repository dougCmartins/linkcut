<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\FixedExpenseData;
use Domain\Finance\Data\FixedExpenseInputData;
use Domain\Finance\Models\FixedExpense;
use Illuminate\Support\Facades\Log;

final class CreateFixedExpense
{
    public function handle(FixedExpenseInputData $data): FixedExpenseData
    {
        $expense = FixedExpense::query()->create([
            'name' => trim($data->name),
            'amount_in_cents' => $data->amount_in_cents,
            'due_day' => $data->due_day,
        ]);
        Log::info('finance_fixed_expense_created', ['expense_id' => $expense->id]);

        return FixedExpenseData::from($expense);
    }
}

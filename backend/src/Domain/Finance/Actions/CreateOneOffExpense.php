<?php

declare(strict_types=1);

namespace Domain\Finance\Actions;

use Domain\Finance\Data\OneOffExpenseData;
use Domain\Finance\Data\OneOffExpenseInputData;
use Domain\Finance\Models\OneOffExpense;
use Illuminate\Support\Facades\Log;

final class CreateOneOffExpense
{
    public function __construct(private readonly EnsureExpenseDateIsNotFuture $ensureDate) {}

    public function handle(OneOffExpenseInputData $data): OneOffExpenseData
    {
        $this->ensureDate->handle($data);
        $expense = OneOffExpense::query()->create([
            'description' => trim($data->description),
            'amount_in_cents' => $data->amount_in_cents,
            'date' => $data->date,
        ]);
        Log::info('finance_one_off_expense_created', ['expense_id' => $expense->id]);

        return OneOffExpenseData::from($expense);
    }
}

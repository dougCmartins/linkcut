<?php

declare(strict_types=1);

namespace Domain\Finance\Controllers;

use Domain\Finance\Actions\CreateFixedExpense;
use Domain\Finance\Actions\CreateOneOffExpense;
use Domain\Finance\Actions\DeleteFixedExpense;
use Domain\Finance\Actions\DeleteOneOffExpense;
use Domain\Finance\Actions\ListExpenses;
use Domain\Finance\Actions\RestoreFixedExpense;
use Domain\Finance\Actions\RestoreOneOffExpense;
use Domain\Finance\Actions\UpdateFixedExpense;
use Domain\Finance\Actions\UpdateOneOffExpense;
use Domain\Finance\Data\ExpenseIdData;
use Domain\Finance\Data\ExpenseListData;
use Domain\Finance\Data\FixedExpenseData;
use Domain\Finance\Data\FixedExpenseInputData;
use Domain\Finance\Data\OneOffExpenseData;
use Domain\Finance\Data\OneOffExpenseInputData;
use Illuminate\Http\Response;

final class ExpenseController
{
    public function index(ListExpenses $action): ExpenseListData
    {
        return $action->handle();
    }

    public function storeFixed(FixedExpenseInputData $data, CreateFixedExpense $action): FixedExpenseData
    {
        return $action->handle($data);
    }

    public function updateFixed(int $id, FixedExpenseInputData $data, UpdateFixedExpense $action): FixedExpenseData
    {
        return $action->handle(self::id($id), $data);
    }

    public function destroyFixed(int $id, DeleteFixedExpense $action): Response
    {
        $action->handle(self::id($id));

        return response()->noContent();
    }

    public function restoreFixed(int $id, RestoreFixedExpense $action): FixedExpenseData
    {
        return $action->handle(self::id($id));
    }

    public function storeOneOff(OneOffExpenseInputData $data, CreateOneOffExpense $action): OneOffExpenseData
    {
        return $action->handle($data);
    }

    public function updateOneOff(int $id, OneOffExpenseInputData $data, UpdateOneOffExpense $action): OneOffExpenseData
    {
        return $action->handle(self::id($id), $data);
    }

    public function destroyOneOff(int $id, DeleteOneOffExpense $action): Response
    {
        $action->handle(self::id($id));

        return response()->noContent();
    }

    public function restoreOneOff(int $id, RestoreOneOffExpense $action): OneOffExpenseData
    {
        return $action->handle(self::id($id));
    }

    private static function id(int $id): ExpenseIdData
    {
        return ExpenseIdData::from(ExpenseIdData::validate(['id' => $id]));
    }
}

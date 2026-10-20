<?php

declare(strict_types=1);

use Domain\Finance\Controllers\ExpenseController;
use Domain\Finance\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('finance')->group(function (): void {
    Route::get('history', [FinanceController::class, 'history']);
    Route::get('history/export', [FinanceController::class, 'exportHistory']);
    Route::get('month-to-date', [FinanceController::class, 'monthToDate']);

    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::whereNumber('id')->group(function (): void {
        Route::post('fixed-expenses', [ExpenseController::class, 'storeFixed']);
        Route::put('fixed-expenses/{id}', [ExpenseController::class, 'updateFixed']);
        Route::delete('fixed-expenses/{id}', [ExpenseController::class, 'destroyFixed']);
        Route::post('fixed-expenses/{id}/restore', [ExpenseController::class, 'restoreFixed']);

        Route::post('one-off-expenses', [ExpenseController::class, 'storeOneOff']);
        Route::put('one-off-expenses/{id}', [ExpenseController::class, 'updateOneOff']);
        Route::delete('one-off-expenses/{id}', [ExpenseController::class, 'destroyOneOff']);
        Route::post('one-off-expenses/{id}/restore', [ExpenseController::class, 'restoreOneOff']);
    });
});

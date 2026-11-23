<?php

declare(strict_types=1);

use Domain\Finance\Controllers\CommissionController;
use Domain\Finance\Controllers\ExpenseController;
use Domain\Finance\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('finance')->group(function (): void {
    Route::get('history', [FinanceController::class, 'history']);
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

    Route::prefix('commission')->group(function (): void {
        Route::get('rules', [CommissionController::class, 'rules']);
        Route::post('rules', [CommissionController::class, 'storeRule']);
        Route::delete('rules/{id}', [CommissionController::class, 'destroyRule'])->whereNumber('id');

        Route::get('adjustments', [CommissionController::class, 'adjustments']);
        Route::post('adjustments', [CommissionController::class, 'storeAdjustment']);
        Route::put('adjustments/{id}', [CommissionController::class, 'updateAdjustment'])->whereNumber('id');
        Route::delete('adjustments/{id}', [CommissionController::class, 'destroyAdjustment'])->whereNumber('id');

        Route::post('simulate', [CommissionController::class, 'simulate']);

        Route::get('closings', [CommissionController::class, 'closings']);
        Route::post('closings', [CommissionController::class, 'close']);
        Route::post('closings/{id}/reopen', [CommissionController::class, 'reopenClosing'])->whereNumber('id');
    });
});

<?php

declare(strict_types=1);

use Domain\Schedule\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('schedule')->whereNumber('id')->group(function (): void {
    Route::get('requests', [ScheduleController::class, 'requests']);
    Route::get('today', [ScheduleController::class, 'today']);
    Route::patch('requests/{id}/accept', [ScheduleController::class, 'accept']);
    Route::patch('requests/{id}/reject', [ScheduleController::class, 'reject']);
});

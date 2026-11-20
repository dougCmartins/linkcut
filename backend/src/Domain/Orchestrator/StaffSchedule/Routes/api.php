<?php

declare(strict_types=1);

use Domain\Orchestrator\StaffSchedule\Controllers\StaffScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('team/staff/{id}')->whereNumber('id')->group(function (): void {
    Route::get('hours', [StaffScheduleController::class, 'hours']);
    Route::put('hours', [StaffScheduleController::class, 'updateHours']);
    Route::get('services', [StaffScheduleController::class, 'services']);
    Route::put('services', [StaffScheduleController::class, 'updateServices']);
    Route::get('blocks', [StaffScheduleController::class, 'blocks']);
    Route::post('blocks', [StaffScheduleController::class, 'storeBlock']);
    Route::delete('blocks/{blockId}', [StaffScheduleController::class, 'destroyBlock'])->whereNumber('blockId');
});

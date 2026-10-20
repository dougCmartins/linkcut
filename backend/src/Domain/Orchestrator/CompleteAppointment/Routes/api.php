<?php

declare(strict_types=1);

use Domain\Orchestrator\CompleteAppointment\Controllers\CompleteAppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('schedule/requests/{id}')->whereNumber('id')->group(function (): void {
    Route::post('complete', [CompleteAppointmentController::class, 'complete']);
    Route::post('reopen', [CompleteAppointmentController::class, 'reopen']);
});

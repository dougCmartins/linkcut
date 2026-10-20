<?php

declare(strict_types=1);

use Domain\Orchestrator\ClientAppointments\Controllers\ClientAppointmentsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:client')->prefix('client')->group(function (): void {
    Route::get('appointments', [ClientAppointmentsController::class, 'index']);
});

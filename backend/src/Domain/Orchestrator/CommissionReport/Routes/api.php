<?php

declare(strict_types=1);

use Domain\Orchestrator\CommissionReport\Controllers\CommissionReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('finance/commission')->group(function (): void {
    Route::get('report', [CommissionReportController::class, 'index']);
});

Route::middleware(['auth:api', 'tenant', 'throttle:reports'])
    ->post('finance/commission/report', [CommissionReportController::class, 'store']);

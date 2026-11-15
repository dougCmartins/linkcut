<?php

declare(strict_types=1);

use Domain\Orchestrator\HistoryReport\Controllers\HistoryReportController;
use Illuminate\Support\Facades\Route;

// "Exportar CSV": the report of the period goes by e-mail, built in the queue.
Route::middleware(['auth:api', 'tenant', 'throttle:reports'])
    ->post('finance/history/report', [HistoryReportController::class, 'store']);

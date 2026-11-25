<?php

declare(strict_types=1);

use Domain\Orchestrator\BrandReport\Controllers\BrandReportController;
use Illuminate\Support\Facades\Route;

// Brand-wide reports: the sum of every unit of the owner's organization (the owner only).
Route::middleware(['auth:api', 'tenant'])->get('finance/brand/report', [BrandReportController::class, 'show']);

Route::middleware(['auth:api', 'tenant', 'throttle:reports'])
    ->post('finance/brand/report', [BrandReportController::class, 'store']);

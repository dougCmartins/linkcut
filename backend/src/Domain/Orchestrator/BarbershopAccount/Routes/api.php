<?php

declare(strict_types=1);

use Domain\Orchestrator\BarbershopAccount\Controllers\BarbershopAccountController;
use Illuminate\Support\Facades\Route;

// The barber's own barbershop account (LGPD): download everything, or delete everything.
Route::middleware(['auth:api', 'tenant'])->prefix('account')->group(function (): void {
    Route::get('export', [BarbershopAccountController::class, 'export'])->middleware('throttle:uploads');
    Route::delete('/', [BarbershopAccountController::class, 'destroy'])->middleware('throttle:login');
});

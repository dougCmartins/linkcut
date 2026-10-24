<?php

declare(strict_types=1);

use Domain\Orchestrator\ClientAccount\Controllers\ClientAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:client')->prefix('client')->group(function (): void {
    Route::delete('me', [ClientAccountController::class, 'destroy'])->middleware('throttle:client-login');
    Route::get('me/export', [ClientAccountController::class, 'export'])->middleware('throttle:uploads');
});

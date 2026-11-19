<?php

declare(strict_types=1);

use Domain\Team\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

// "Minha equipe" of the current unit (docs/decisoes/0001 P3): only the owner manages it.
Route::middleware(['auth:api', 'tenant', 'role.owner'])->prefix('team/staff')->group(function (): void {
    Route::get('/', [StaffController::class, 'index']);
    Route::post('/', [StaffController::class, 'store'])->middleware('throttle:uploads');
    Route::whereNumber('id')->group(function (): void {
        Route::post('{id}/invitation', [StaffController::class, 'resendInvitation'])->middleware('throttle:uploads');
    });
});

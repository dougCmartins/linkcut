<?php

declare(strict_types=1);

use Domain\Tenant\Controllers\TenantController;
use Domain\Tenant\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('tenant', [TenantController::class, 'show']);
    Route::post('tenant/onboarding/complete', [TenantController::class, 'completeOnboarding'])->middleware('role.owner');
    // The unit picker at the top of the dashboard (docs/adr/0002).
    Route::get('organization/units', [UnitController::class, 'index']);
});

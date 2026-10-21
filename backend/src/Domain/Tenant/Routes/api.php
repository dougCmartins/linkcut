<?php

declare(strict_types=1);

use Domain\Tenant\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('tenant', [TenantController::class, 'show']);
    Route::post('tenant/onboarding/complete', [TenantController::class, 'completeOnboarding']);
});

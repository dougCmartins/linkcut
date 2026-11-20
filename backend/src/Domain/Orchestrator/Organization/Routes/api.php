<?php

declare(strict_types=1);

use Domain\Orchestrator\Organization\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('organization', [OrganizationController::class, 'show']);
    Route::post('organization/units', [OrganizationController::class, 'storeUnit']);
});

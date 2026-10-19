<?php

declare(strict_types=1);

use Domain\Tenant\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->get('tenant', [TenantController::class, 'show']);

<?php

declare(strict_types=1);

use Domain\Orchestrator\Units\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// "Nova filial": a branch of the owner's organization (docs/adr/0002, docs/decisoes/0001 P1).
Route::middleware(['auth:api', 'tenant', 'throttle:uploads'])->post('organization/units', [UnitController::class, 'store']);

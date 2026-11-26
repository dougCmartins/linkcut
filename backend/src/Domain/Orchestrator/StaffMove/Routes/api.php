<?php

declare(strict_types=1);

use Domain\Orchestrator\StaffMove\Controllers\StaffMoveController;
use Illuminate\Support\Facades\Route;

// "Mover para outra filial" (docs/decisoes/0003): the barber of the unit picked at the top goes to another unit.
Route::middleware(['auth:api', 'tenant'])->post('team/staff/{id}/move', [StaffMoveController::class, 'store'])->whereNumber('id');

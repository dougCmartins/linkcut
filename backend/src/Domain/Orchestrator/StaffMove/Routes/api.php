<?php

declare(strict_types=1);

use Domain\Orchestrator\StaffMove\Controllers\LoanController;
use Domain\Orchestrator\StaffMove\Controllers\StaffMoveController;
use Illuminate\Support\Facades\Route;

// "Mover para outra filial" (docs/decisoes/0003): the barber of the unit picked at the top goes to another unit.
Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::post('team/staff/{id}/move', [StaffMoveController::class, 'store'])->whereNumber('id');
    // The move form starts with the destination's hours and services; "Minha equipe" lists the current moves.
    Route::get('team/staff/{id}/move-options', [StaffMoveController::class, 'options'])->whereNumber('id');
    Route::get('team/moves', [StaffMoveController::class, 'index']);
});

// An active loan (docs/decisoes/0003 P2): extend it, end it now (the barber comes back) or make it a transfer.
Route::middleware(['auth:api', 'tenant'])->prefix('team/moves/{id}')->whereNumber('id')->group(function (): void {
    Route::patch('extend', [LoanController::class, 'extend']);
    Route::post('end', [LoanController::class, 'end']);
    Route::post('make-permanent', [LoanController::class, 'makePermanent']);
});

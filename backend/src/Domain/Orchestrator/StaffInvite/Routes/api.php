<?php

declare(strict_types=1);

use Domain\Orchestrator\StaffInvite\Controllers\PublicStaffInviteController;
use Domain\Orchestrator\StaffInvite\Controllers\TeamStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('team/staff', [TeamStaffController::class, 'index']);
    Route::post('team/staff', [TeamStaffController::class, 'store']);
    Route::patch('team/staff/{id}/active', [TeamStaffController::class, 'updateActive'])->whereNumber('id');
    Route::patch('team/staff/{id}/permissions', [TeamStaffController::class, 'updatePermissions'])->whereNumber('id');
});

Route::middleware('throttle:public')->group(function (): void {
    Route::get('public/staff-invites/{token}', [PublicStaffInviteController::class, 'show'])->where('token', '[A-Za-z0-9]+');
    Route::post('public/staff-invites/{token}', [PublicStaffInviteController::class, 'accept'])->where('token', '[A-Za-z0-9]+');
});

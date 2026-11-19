<?php

declare(strict_types=1);

use Domain\Orchestrator\Team\Controllers\InvitationController;
use Domain\Orchestrator\Team\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

// The link of the team invitation e-mail (public: the barber has no login yet). Same limit as the sign-in.
Route::post('team/invitations/accept', [InvitationController::class, 'accept'])->middleware('throttle:login');

// The owner turns a barber on/off and sets their switches; turning off also ends the barber's sessions.
Route::middleware(['auth:api', 'tenant', 'role.owner'])->put('team/staff/{id}', [TeamMemberController::class, 'update'])->whereNumber('id');

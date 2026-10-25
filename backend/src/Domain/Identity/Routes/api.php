<?php

declare(strict_types=1);

use Domain\Identity\Controllers\AuthController;
use Domain\Identity\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::post('two-factor/send', [TwoFactorController::class, 'send'])->middleware('throttle:two-factor');
Route::post('token/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me/two-factor-channel', [AuthController::class, 'updateTwoFactorChannel']);
    Route::post('logout', [AuthController::class, 'logout']);
});

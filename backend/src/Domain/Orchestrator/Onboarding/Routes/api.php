<?php

declare(strict_types=1);

use Domain\Orchestrator\Onboarding\Controllers\SignUpController;
use Illuminate\Support\Facades\Route;

// Open sign-up of barbers (no session yet).
Route::prefix('sign-up')->group(function (): void {
    Route::post('code', [SignUpController::class, 'sendCode'])->middleware('throttle:sign-up-codes');
    Route::get('slug', [SignUpController::class, 'slug'])->middleware('throttle:public');
    Route::post('/', [SignUpController::class, 'signUp'])->middleware('throttle:sign-up');
});

<?php

declare(strict_types=1);

use Domain\Client\Controllers\ClientAuthController;
use Domain\Client\Controllers\ClientEmailController;
use Illuminate\Support\Facades\Route;

// Client area ("Meus horários"). Its own guard: a client token never opens a barber route.
Route::prefix('client')->group(function (): void {
    Route::post('codes', [ClientAuthController::class, 'sendCode'])->middleware('throttle:client-codes');
    Route::post('registration', [ClientAuthController::class, 'register'])->middleware('throttle:client-login');
    Route::post('password', [ClientAuthController::class, 'resetPassword'])->middleware('throttle:client-login');
    Route::post('login', [ClientAuthController::class, 'login'])->middleware('throttle:client-login');
    Route::get('auth/google/redirect', [ClientAuthController::class, 'redirectToGoogle'])->middleware('throttle:client-login');
    Route::get('auth/google/callback', [ClientAuthController::class, 'googleCallback'])->middleware('throttle:client-login');
    Route::post('auth/google/exchange', [ClientAuthController::class, 'exchangeGoogle'])->middleware('throttle:client-login');
    Route::post('token/refresh', [ClientAuthController::class, 'refresh'])->middleware('throttle:refresh');
    Route::get('emails/opt-out/{client}', [ClientEmailController::class, 'optOut'])
        ->whereNumber('client')->middleware('signed')->name('client.emails.opt-out');

    Route::middleware('auth:client')->group(function (): void {
        Route::post('logout', [ClientAuthController::class, 'logout']);
        Route::get('me', [ClientAuthController::class, 'me']);
        Route::patch('me', [ClientAuthController::class, 'updateProfile']);
        Route::put('me/password', [ClientAuthController::class, 'changePassword'])->middleware('throttle:client-login');
        Route::put('me/emails', [ClientAuthController::class, 'updateEmailPreference']);
    });
});

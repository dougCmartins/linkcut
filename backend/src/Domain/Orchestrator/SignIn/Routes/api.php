<?php

declare(strict_types=1);

use Domain\Orchestrator\SignIn\Controllers\SignInController;
use Illuminate\Support\Facades\Route;

// Step 1 happens on the barbershop's own page (/<slug>/entrar); /entrar only e-mails that link.
Route::post('login', [SignInController::class, 'login'])->middleware('throttle:login');
Route::post('login/link', [SignInController::class, 'sendLink'])->middleware('throttle:login');
Route::post('two-factor/verify', [SignInController::class, 'verify'])->middleware('throttle:two-factor');

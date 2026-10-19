<?php

declare(strict_types=1);

use Domain\Orchestrator\SignIn\Controllers\SignInController;
use Illuminate\Support\Facades\Route;

Route::post('two-factor/verify', [SignInController::class, 'verify'])->middleware('throttle:two-factor');

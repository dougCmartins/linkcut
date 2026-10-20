<?php

declare(strict_types=1);

use Domain\Orchestrator\AnswerBookingRequest\Controllers\AnswerBookingRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('schedule/requests/{id}')->whereNumber('id')->group(function (): void {
    Route::patch('accept', [AnswerBookingRequestController::class, 'accept']);
    Route::patch('reject', [AnswerBookingRequestController::class, 'reject']);
});

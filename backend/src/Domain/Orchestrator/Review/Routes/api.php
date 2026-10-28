<?php

declare(strict_types=1);

use Domain\Orchestrator\Review\Controllers\BarberReviewsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('reviews', [BarberReviewsController::class, 'index']);
    Route::post('reviews/{review}/hide', [BarberReviewsController::class, 'hide'])->whereNumber('review');
    Route::put('reviews/featured', [BarberReviewsController::class, 'feature']);
});

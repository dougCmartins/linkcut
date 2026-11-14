<?php

declare(strict_types=1);

use Domain\Orchestrator\ClientAppointments\Controllers\ClientAppointmentsController;
use Domain\Orchestrator\ClientAppointments\Controllers\ClientBarbershopProductsController;
use Domain\Orchestrator\Review\Controllers\ClientReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:client')->prefix('client')->group(function (): void {
    Route::get('appointments', [ClientAppointmentsController::class, 'index']);
    Route::get('barbershops/{slug}/products', [ClientBarbershopProductsController::class, 'index'])
        ->middleware('throttle:public')
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
    Route::post('appointments/{appointment}/cancel', [ClientAppointmentsController::class, 'cancel'])->whereNumber('appointment');
    Route::post('appointments/{appointment}/review', [ClientReviewController::class, 'store'])
        ->whereNumber('appointment')
        ->middleware('throttle:client-reviews');
});

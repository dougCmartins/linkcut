<?php

declare(strict_types=1);

use Domain\Orchestrator\PublicBooking\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:public', 'tenant.slug'])
    ->prefix('public/{slug}')
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->group(function (): void {
        Route::get('availability', [PublicBookingController::class, 'availability']);
        Route::post('booking-requests', [PublicBookingController::class, 'store'])->middleware('throttle:booking');
    });

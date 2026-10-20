<?php

declare(strict_types=1);

use Domain\Notification\Controllers\PushController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('notifications/push')->group(function (): void {
    Route::get('public-key', [PushController::class, 'publicKey']);
    Route::post('subscriptions', [PushController::class, 'subscribe']);
    Route::delete('subscriptions', [PushController::class, 'unsubscribe']);
});

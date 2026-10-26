<?php

declare(strict_types=1);

use Domain\Notification\Controllers\ClientPushController;
use Domain\Notification\Controllers\PushController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('notifications/push')->group(function (): void {
    Route::get('public-key', [PushController::class, 'publicKey']);
    Route::post('subscriptions', [PushController::class, 'subscribe']);
    Route::delete('subscriptions', [PushController::class, 'unsubscribe']);
});

// Clients: told when the barbershop answers. Right after booking the client has no session, so the booking answer
// carries a short-lived signed link for that client only.
Route::get('public/push-key', [ClientPushController::class, 'publicKey'])->middleware('throttle:public');
Route::post('public/client-push/{client}', [ClientPushController::class, 'subscribeAfterBooking'])
    ->whereNumber('client')
    ->middleware(['signed:relative', 'throttle:public'])
    ->name('client-push.subscribe');
Route::post('client/push-subscriptions', [ClientPushController::class, 'subscribeSignedIn'])->middleware(['auth:client', 'throttle:public']);

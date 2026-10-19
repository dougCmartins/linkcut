<?php

declare(strict_types=1);

use Domain\Settings\Controllers\PublicPageController;
use Domain\Settings\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('settings')->group(function (): void {
    Route::get('/', [SettingsController::class, 'show']);
    Route::put('profile', [SettingsController::class, 'updateProfile']);
    Route::put('portfolio', [SettingsController::class, 'updatePortfolio']);
    Route::put('schedule', [SettingsController::class, 'updateSchedule']);
    Route::put('theme', [SettingsController::class, 'updateTheme']);
    Route::post('images', [SettingsController::class, 'uploadImage'])->middleware('throttle:uploads');
});

// Badly formed slugs are a plain 404, like unknown ones.
Route::middleware(['throttle:public', 'tenant.slug'])->prefix('public/{slug}')->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->group(function (): void {
    Route::get('/', [PublicPageController::class, 'show']);
    Route::get('booking-options', [PublicPageController::class, 'bookingOptions']);
});

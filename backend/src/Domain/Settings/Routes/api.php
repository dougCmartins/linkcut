<?php

declare(strict_types=1);

use Domain\Settings\Controllers\PublicPageController;
use Domain\Settings\Controllers\SettingsController;
use Domain\Settings\Controllers\ShareCardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->prefix('settings')->group(function (): void {
    Route::get('/', [SettingsController::class, 'show']);
    // Looks the address up on OpenStreetMap: limited, so nobody gets the server banned there.
    Route::put('profile', [SettingsController::class, 'updateProfile'])->middleware('throttle:uploads');
    Route::put('portfolio', [SettingsController::class, 'updatePortfolio']);
    Route::put('schedule', [SettingsController::class, 'updateSchedule']);
    Route::put('theme', [SettingsController::class, 'updateTheme']);
    Route::post('images', [SettingsController::class, 'uploadImage'])->middleware('throttle:uploads');
});

// Badly formed slugs are a plain 404, like unknown ones. The ETag lets a returning visitor get an empty 304;
// "no-cache" makes the browser ask every time, so a barber sees the change right after saving.
Route::middleware(['throttle:public', 'tenant.slug', 'cache.headers:public;no_cache;etag'])
    ->prefix('public/{slug}')->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->group(function (): void {
        Route::get('/', [PublicPageController::class, 'show']);
        Route::get('booking-options', [PublicPageController::class, 'bookingOptions']);
    });

// Link previews (Open Graph) for crawlers that do not run JavaScript; deploy/Caddyfile routes them here.
Route::middleware(['throttle:public', 'tenant.slug'])->prefix('og/{slug}')->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->group(function (): void {
    Route::get('/', [ShareCardController::class, 'bioLink']);
    // A shared booking link previews the barbershop (/rafael/agendar, /rafael/portfolio/agendar).
    Route::get('agendar', [ShareCardController::class, 'bioLink']);
    Route::get('imagem', [ShareCardController::class, 'bioLinkImage']);
    Route::get('portfolio', [ShareCardController::class, 'portfolio']);
    Route::get('portfolio/agendar', [ShareCardController::class, 'portfolio']);
    Route::get('portfolio/imagem', [ShareCardController::class, 'portfolioImage']);
});

<?php

declare(strict_types=1);

use Domain\Orchestrator\PublicPage\Controllers\PublicBrandController;
use Domain\Orchestrator\PublicPage\Controllers\PublicVisitController;
use Illuminate\Support\Facades\Route;

// The public page of a barbershop: its profile (Settings) with the rating and the featured reviews (Review).
// Same guards as the other public routes: badly formed slugs are a plain 404, and the ETag allows an empty 304.
Route::middleware(['throttle:public', 'tenant.slug', 'cache.headers:public;no_cache;etag'])
    ->prefix('public/{slug}')->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->group(function (): void {
        Route::get('/', [PublicVisitController::class, 'show']);
    });

// The brand link (docs/decisoes/0001 P2): the active units of a brand with branches, for the picker on its page.
// No tenant here: a brand sits above its units. "brand" is a reserved slug, so no barbershop can take this path.
Route::middleware(['throttle:public', 'cache.headers:public;no_cache;etag'])
    ->get('public/brand/{slug}', [PublicBrandController::class, 'show'])
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']);

<?php

declare(strict_types=1);

use Domain\Orchestrator\PublicBrand\Controllers\PublicBrandController;
use Illuminate\Support\Facades\Route;

// Brand (organization) public link: list of active units. Registered before public/{slug} groups.
Route::middleware(['throttle:public', 'cache.headers:public;no_cache;etag'])
    ->get('public/brand/{slug}', [PublicBrandController::class, 'show'])
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']);

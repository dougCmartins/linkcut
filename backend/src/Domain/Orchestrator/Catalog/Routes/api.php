<?php

declare(strict_types=1);

use Domain\Orchestrator\Catalog\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    // Registered before {id}. Otherwise "order" is read as a product id.
    Route::put('products/order', [ProductController::class, 'reorder']);
    Route::whereNumber('id')->group(function (): void {
        Route::put('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
    });
});

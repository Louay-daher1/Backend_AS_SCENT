<?php

use App\Http\Controllers\Api\V1\CartPreviewController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SlideController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{productId}/price', [ProductController::class, 'variantPrice'])
        ->whereNumber('productId');
    Route::get('/products/{productId}', [ProductController::class, 'show'])
        ->whereNumber('productId');
    Route::get('/slides', [SlideController::class, 'index']);

    Route::post('/cart/preview', [CartPreviewController::class, 'store']);
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
});

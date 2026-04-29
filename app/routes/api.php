<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/login', [AuthController::class, 'login']);

// Public export download (accessed via signed URL, no token needed)
Route::get('/products/export', [ProductController::class, 'export'])
    ->name('products.export.download');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CategoryController::class, 'index']);

    // Must be before apiResource to avoid {product} capturing these slugs
    Route::delete('/products/bulk', [ProductController::class, 'bulkDelete']);
    Route::get('/products/export-link', [ProductController::class, 'exportLink']);

    Route::apiResource('products', ProductController::class);
});

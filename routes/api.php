<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::middleware('api')->group(function () {

    // Products (Public View)
    Route::get('products', [ProductController::class, 'index'])
        ->middleware('permission:view products');

    Route::get('products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:view products');

    // Categories (Public View)
    Route::get('categories', [CategoryController::class, 'index'])
        ->middleware('permission:view categories');

    Route::get('categories/{category}', [CategoryController::class, 'show'])
        ->middleware('permission:view categories');

    Route::get('categories/{category}/products', [CategoryController::class, 'products'])
        ->middleware('permission:view products');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Products Management
    |--------------------------------------------------------------------------
    */
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:create products');

    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:update products');

    Route::patch('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:update products');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:delete products');

    Route::post('products/{product}/restore', [ProductController::class, 'restore'])
        ->middleware('permission:restore products');

    Route::delete('products/{product}/force', [ProductController::class, 'forceDelete'])
        ->middleware('permission:force delete products');

    /*
    |--------------------------------------------------------------------------
    | Categories Management
    |--------------------------------------------------------------------------
    */
    Route::post('categories', [CategoryController::class, 'store'])
        ->middleware('permission:create categories');

    Route::put('categories/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:update categories');

    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:delete categories');
});

<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);

Route::apiResource('products', ProductController::class)
    ->except(['index', 'show'])
    ->middleware(['auth:sanctum', 'permission:create products']);

include_once __DIR__.'/auth.php';
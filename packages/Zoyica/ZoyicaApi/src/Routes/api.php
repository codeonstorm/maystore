<?php

use Illuminate\Support\Facades\Route;
use Zoyica\ZoyicaApi\Http\Controllers\V1\Admin\Catalog\ProductController;

Route::prefix('api/v1/zoyica/admin')
    ->middleware(['api', 'auth:sanctum', 'sanctum.admin'])
    ->group(function () {
        Route::patch('products/{id}/price-stock', [ProductController::class, 'update']);
    });

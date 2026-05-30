<?php

use Illuminate\Support\Facades\Route;
use Zoyica\ZoyicaVisitor\Http\Controllers\Admin\AnalyticsController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url'),
], function () {
    Route::get('zoyica/analytics', [AnalyticsController::class, 'index'])
        ->name('zoyica.analytics');
});

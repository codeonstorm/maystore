<?php

use Illuminate\Support\Facades\Route;
use Zoyica\ZoyicaTheme\Http\Controllers\Admin\ZoyicaThemeController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin/zoyicatheme'], function () {
    Route::controller(ZoyicaThemeController::class)->group(function () {
        Route::get('', 'index')->name('admin.zoyicatheme.index');
    });
});
<?php

use Illuminate\Support\Facades\Route;
use Zoyica\ZoyicaTheme\Http\Controllers\Shop\ZoyicaThemeController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency'], 'prefix' => 'zoyicatheme'], function () {
    Route::get('', [ZoyicaThemeController::class, 'index'])->name('shop.zoyicatheme.index');
});
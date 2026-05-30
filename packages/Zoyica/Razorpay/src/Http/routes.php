<?php

use Illuminate\Support\Facades\Route;
use Zoyica\Razorpay\Http\Controllers\RazorpayController;

Route::group(['middleware' => ['web', 'throttle:30,1']], function () {
    Route::prefix('razorpay')->group(function () {
        Route::get('/redirect', [RazorpayController::class, 'redirect'])
            ->name('razorpay.redirect');

        Route::post('/success', [RazorpayController::class, 'success'])
            ->name('razorpay.success');

        Route::get('/cancel', [RazorpayController::class, 'cancel'])
            ->name('razorpay.cancel');
    });
});

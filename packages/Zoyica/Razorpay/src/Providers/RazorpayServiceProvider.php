<?php

namespace Zoyica\Razorpay\Providers;

use Illuminate\Support\ServiceProvider;

class RazorpayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php',
            'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php',
            'core'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'razorpay');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'razorpay');
    }
}

<?php

namespace Zoyica\ZoyicaApi\Providers;

use Illuminate\Support\ServiceProvider;

class ZoyicaApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}

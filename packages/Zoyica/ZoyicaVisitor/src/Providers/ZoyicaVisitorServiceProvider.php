<?php

namespace Zoyica\ZoyicaVisitor\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Zoyica\ZoyicaVisitor\Http\Middleware\TrackVisitorSession;
use Zoyica\ZoyicaVisitor\Listeners\TrackCartEvent;
use Zoyica\ZoyicaVisitor\Listeners\TrackOrderEvent;

class ZoyicaVisitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/admin-menu.php',
            'menu.admin'
        );
    }

    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'zoyica-visitor');

        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        $router->pushMiddlewareToGroup('web', TrackVisitorSession::class);

        Event::listen('checkout.cart.add.after',    [TrackCartEvent::class, 'onAdd']);
        Event::listen('checkout.cart.delete.after', [TrackCartEvent::class, 'onRemove']);
        Event::listen('checkout.order.save.after',  [TrackOrderEvent::class, 'handle']);
    }
}

<?php

namespace AgenticMorf\FluxUILoki;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FluxUILokiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fluxui-loki.php', 'fluxui-loki');
    }

    public function boot(): void
    {
        $paths = [__DIR__.'/../resources/views'];
        $published = $this->app->resourcePath('views/vendor/fluxui-loki');
        if (is_dir($published)) {
            array_unshift($paths, $published);
        }
        $this->loadViewsFrom($paths, 'fluxui-loki');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fluxui-loki.php' => config_path('fluxui-loki.php'),
            ], 'fluxui-loki-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/fluxui-loki'),
            ], 'fluxui-loki-views');
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware(config('fluxui-loki.middleware', ['web', 'auth']))
            ->group(function () {
                Route::get(
                    config('fluxui-loki.route_path', 'logs'),
                    Livewire\LogsDashboard::class
                )->name(config('fluxui-loki.route_name', 'logs'));
            });
    }
}

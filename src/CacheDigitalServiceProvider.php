<?php

namespace CacheDigital\Core;

use Illuminate\Support\ServiceProvider;

class CacheDigitalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cachedigital.php',
            'cachedigital'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cachedigital.php' => config_path('cachedigital.php'),
            ], 'cachedigital-config');
        }
    }
}

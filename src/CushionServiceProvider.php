<?php

namespace Larasell\Cushion;

use Illuminate\Support\ServiceProvider;
use Larasell\Cushion\Routing\DraftRouteMacro;

class CushionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cushion.php', 'cushion');

        $this->app->singleton(DraftStoreManager::class);
    }

    public function boot(): void
    {
        DraftRouteMacro::register();

        $this->publishes([
            __DIR__.'/../config/cushion.php' => $this->app->configPath('cushion.php'),
        ], 'cushion.config');
    }
}

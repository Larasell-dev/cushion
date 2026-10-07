<?php

namespace Larasell\FormDrafts;

use Illuminate\Support\ServiceProvider;
use Larasell\FormDrafts\Routing\DraftRouteMacro;

class FormDraftsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/form-drafts.php', 'form-drafts');

        $this->app->singleton(DraftStoreManager::class);
    }

    public function boot(): void
    {
        DraftRouteMacro::register();

        $this->publishes([
            __DIR__.'/../config/form-drafts.php' => $this->app->configPath('form-drafts.php'),
        ], 'form-drafts.config');
    }
}

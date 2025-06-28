<?php

namespace Ijideals\SearchEngine\Providers;

use Illuminate\Support\ServiceProvider;

class SearchEngineServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/search-engine.php' => config_path('search-engine.php'),
            ], 'search-engine-config');
        }

        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/search-engine.php',
            'search-engine'
        );
    }
}

<?php

namespace Ijideals\CatalogManager\Providers;

use Illuminate\Support\ServiceProvider;

class CatalogManagerServiceProvider extends ServiceProvider
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
                __DIR__.'/../../config/catalog-manager.php' => config_path('catalog-manager.php'),
            ], 'catalog-manager-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'catalog-manager-migrations');

            // $this->publishes([
            //     __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/catalog-manager'),
            // ], 'catalog-manager-lang');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php'); // Temporarily commented out to avoid TypeError
        // $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'catalog-manager');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/catalog-manager.php',
            'catalog-manager'
        );

        // Register any services specific to this package here
    }
}

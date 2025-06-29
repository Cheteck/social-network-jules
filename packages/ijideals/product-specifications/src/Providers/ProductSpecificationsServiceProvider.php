<?php

namespace IJIDeals\ProductSpecifications\Providers;

use Illuminate\Support\ServiceProvider;

class ProductSpecificationsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        // Example for publishing config:
        /*
        $this->publishes([
            __DIR__.'/../../config/product-specifications.php' => config_path('product-specifications.php'),
        ], 'config');
        */
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Example for merging config:
        /*
        $this->mergeConfigFrom(
            __DIR__.'/../../config/product-specifications.php', 'product-specifications'
        );
        */
    }
}

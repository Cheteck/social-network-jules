<?php

namespace Ijideals\ShopManager\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Ijideals\ShopManager\Models\Shop;
use Ijideals\ShopManager\Policies\ShopPolicy;

class ShopManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/shop-manager.php',
            'shop-manager'
        );

        // Register any services specific to this package here
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/shop-manager.php' => config_path('shop-manager.php'),
            ], 'shop-manager-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'shop-manager-migrations');

            $this->publishes([
                __DIR__.'/../../database/seeders/' => database_path('seeders'),
            ], 'shop-manager-seeders');

            // Language files can be added later if needed
            // $this->publishes([
            //     __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/shop-manager'),
            // ], 'shop-manager-lang');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
        // $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'shop-manager');
    }

    /**
     * Register the package's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        Gate::policy(Shop::class, ShopPolicy::class);
    }
}

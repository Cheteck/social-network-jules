<?php

namespace Ijideals\UserProfile\Providers;

use Illuminate\Support\ServiceProvider;

class UserProfileServiceProvider extends ServiceProvider
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
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'user-profile-migrations');

            // Config file (if any)
            /*
            $this->publishes([
                __DIR__.'/../../config/user-profile.php' => config_path('user-profile.php'),
            ], 'user-profile-config');
            */
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load routes (if any)
        // $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config (if any)
        /*
        $this->mergeConfigFrom(
            __DIR__.'/../../config/user-profile.php',
            'user-profile'
        );
        */

        // Register any services specific to this package here
    }
}

<?php

namespace Ijideals\Followable\Providers;

use Illuminate\Support\ServiceProvider;

class FollowableServiceProvider extends ServiceProvider
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
                __DIR__.'/../../config/followable.php' => config_path('followable.php'),
            ], 'followable-config');

            // Assuming migrations are handled by the main app or were published previously
            // If not, uncomment and adjust:
            // $this->publishes([
            //     __DIR__.'/../../database/migrations/' => database_path('migrations'),
            // ], 'followable-migrations');
        }
        // $this->loadMigrationsFrom(__DIR__.'/../../database/migrations'); // If migrations are part of the package
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/followable.php',
            'followable'
        );
    }
}

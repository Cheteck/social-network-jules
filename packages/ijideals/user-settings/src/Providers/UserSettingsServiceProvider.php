<?php

namespace Ijideals\UserSettings\Providers;

use Illuminate\Support\ServiceProvider;

class UserSettingsServiceProvider extends ServiceProvider
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
                __DIR__.'/../../config/user-settings.php' => config_path('user-settings.php'),
            ], 'user-settings-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'user-settings-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
        // No translations needed for this package's direct responses for now
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/user-settings.php',
            'user-settings'
        );

        // No specific services to register for now, logic will be in trait/controller.
    }
}

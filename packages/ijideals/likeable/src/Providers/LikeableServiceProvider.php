<?php

namespace Ijideals\Likeable\Providers;

use Illuminate\Support\ServiceProvider;

class LikeableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'likeable');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'likeable-migrations');

            $this->publishes([
                __DIR__.'/../../config/likeable.php' => config_path('likeable.php'),
            ], 'likeable-config');

            $this->publishes([
                __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/likeable'),
            ], 'likeable-lang');

            $this->publishes([
                __DIR__.'/../../src/Routes/api.php' => base_path('routes/likeable_api.php'),
            ], 'likeable-routes');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/likeable.php', 'likeable'
        );
    }
}

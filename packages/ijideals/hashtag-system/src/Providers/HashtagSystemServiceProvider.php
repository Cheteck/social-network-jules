<?php

namespace Ijideals\HashtagSystem\Providers;

use Illuminate\Support\ServiceProvider;

class HashtagSystemServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations')
        ], 'hashtag-system-migrations');

        $this->publishes([
            __DIR__.'/../../database/seeders/' => database_path('seeders/vendor/hashtag-system'),
        ], 'hashtag-system-seeders');

        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register any bindings or services
    }
}

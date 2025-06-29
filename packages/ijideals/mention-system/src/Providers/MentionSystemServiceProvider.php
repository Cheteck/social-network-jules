<?php

namespace IJIDeals\MentionSystem\Providers;

use Illuminate\Support\ServiceProvider;

class MentionSystemServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load routes
        // $this->loadRoutesFrom(__DIR__.'/../../routes/api.php'); // Example if API routes are needed

        // Publishing configuration (optional)
        /*
        $this->publishes([
            __DIR__.'/../../config/mention-system.php' => config_path('mention-system.php'),
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
        // Merge configuration (optional)
        /*
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mention-system.php', 'mention-system'
        );
        */

        // Bind services to the container (optional)
        /*
        $this->app->bind('mention-service', function ($app) {
            return new \IJIDeals\MentionSystem\Services\MentionService();
        });
        */
    }
}

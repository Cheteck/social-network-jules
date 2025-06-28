<?php

namespace Ijideals\NotificationSystem\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Ijideals\NotificationSystem\Services\NotificationCreationService;

class NotificationSystemServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/notification-system.php' => config_path('notification-system.php'),
            ], 'notification-system-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'notification-system-migrations');

            $this->publishes([
                __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/notification-system'),
            ], 'notification-system-lang');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'notification-system');

        // Register event listeners if that's the chosen approach
        $this->registerEventListeners($events);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/notification-system.php',
            'notification-system'
        );

        $this->app->singleton(NotificationCreationService::class, function ($app) {
            return new NotificationCreationService();
        });
    }

    /**
     * Register event listeners for notification creation.
     * This method will be populated based on available events from other packages.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    protected function registerEventListeners(Dispatcher $events)
    {
        $eventListenerMap = config('notification-system.event_listeners', []);

        foreach ($eventListenerMap as $event => $listeners) {
            if (!class_exists($event)) {
                // Log or handle missing event class
                continue;
            }
            foreach ((array) $listeners as $listener) {
                if (!class_exists($listener)) {
                    // Log or handle missing listener class
                    continue;
                }
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            NotificationCreationService::class,
        ];
    }
}

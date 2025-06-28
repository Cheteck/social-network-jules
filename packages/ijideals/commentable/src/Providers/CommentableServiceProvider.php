<?php

namespace Ijideals\Commentable\Providers;

use Illuminate\Support\ServiceProvider;

class CommentableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'commentable');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/commentable.php' => config_path('commentable.php'),
            ], 'commentable-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'commentable-migrations');

            $this->publishes([
                __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/commentable'),
            ], 'commentable-lang');
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
            __DIR__.'/../../config/commentable.php',
            'commentable'
        );
    }
}

<?php

namespace Ijideals\MediaUploader\Providers;

use Illuminate\Support\ServiceProvider;
use Ijideals\MediaUploader\Services\MediaUploaderService;

class MediaUploaderServiceProvider extends ServiceProvider
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
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'media-uploader');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/media-uploader.php' => config_path('media-uploader.php'),
            ], 'media-uploader-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations'),
            ], 'media-uploader-migrations');

            $this->publishes([
                __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/media-uploader'),
            ], 'media-uploader-lang');
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
            __DIR__.'/../../config/media-uploader.php',
            'media-uploader'
        );

        $this->app->singleton(MediaUploaderService::class, function ($app) {
            return new MediaUploaderService();
        });
    }
}

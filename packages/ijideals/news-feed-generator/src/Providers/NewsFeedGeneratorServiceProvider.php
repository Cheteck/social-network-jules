<?php

namespace Ijideals\NewsFeedGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Ijideals\NewsFeedGenerator\Services\FeedAggregatorService;
use Ijideals\NewsFeedGenerator\Services\RankingEngineService;
use Ijideals\NewsFeedGenerator\Services\FeedCacheManager;

class NewsFeedGeneratorServiceProvider extends ServiceProvider
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
                __DIR__.'/../../config/news-feed-generator.php' => config_path('news-feed-generator.php'),
            ], 'news-feed-generator-config');

            // No migrations needed for this package initially, as it primarily reads data.
            // Migrations might be added later if we store feed-specific metadata.

            $this->publishes([
                __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/news-feed-generator'),
            ], 'news-feed-generator-lang');
        }

        $this->loadRoutesFrom(__DIR__.'/../../src/Routes/api.php');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'news-feed-generator');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/news-feed-generator.php',
            'news-feed-generator'
        );

        $this->app->singleton(FeedAggregatorService::class, function ($app) {
            return new FeedAggregatorService(
                $app->make(RankingEngineService::class) // Ensure RankingEngineService is injected
            );
        });

        $this->app->singleton(RankingEngineService::class, function ($app) {
            return new RankingEngineService(); // Assuming no direct constructor dependencies for now
        });

        $this->app->singleton(FeedCacheManager::class, function ($app) {
            return new FeedCacheManager($app['cache.store']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            FeedAggregatorService::class,
            RankingEngineService::class,
            FeedCacheManager::class,
        ];
    }
}

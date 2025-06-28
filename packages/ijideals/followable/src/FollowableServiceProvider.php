<?php

namespace Ijideals\Followable;

use Illuminate\Support\ServiceProvider;

class FollowableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    public function register()
    {
        // You can bind any services here if needed
    }
}

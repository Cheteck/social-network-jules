<?php

use Illuminate\Support\Facades\Route;
use Ijideals\SearchEngine\Http\Controllers\SearchController;

Route::prefix(config('search-engine.route_prefix', 'api/v1/search'))
    ->name('search.') // Route name prefix
    ->group(function () {

        // Perform a global search
        // GET /prefix/?q=searchTerm&types=user,post&users_page=1&posts_page=2
        Route::get('/', [SearchController::class, 'search'])->name('global');

    });

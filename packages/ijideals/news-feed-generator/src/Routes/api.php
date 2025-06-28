<?php

use Illuminate\Support\Facades\Route;
use Ijideals\NewsFeedGenerator\Http\Controllers\NewsFeedController;

Route::prefix(config('news-feed-generator.route_prefix', 'api/v1/feed'))
    ->middleware(['api', 'auth:api']) // Ensure all routes in this group require API auth
    ->name('newsfeed.') // Route name prefix
    ->group(function () {

        // Get the authenticated user's news feed
        // GET /prefix/
        Route::get('/', [NewsFeedController::class, 'getFeed'])->name('get');

        // Potentially other feed-related routes in the future:
        // - POST /settings (e.g., to customize feed content types)
        // - POST /item/{itemId}/hide (e.g., to hide a specific item from the feed)
    });

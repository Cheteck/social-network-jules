<?php

use Illuminate\Support\Facades\Route;
use Ijideals\Likeable\Http\Controllers\LikeController;

// The ServiceProvider already wraps route loading with `mapApiRoutes` or similar,
// which often includes the 'api' middleware and a prefix.
// If not, ensure these are applied as needed.
// Example directly in this file (could be redundant if SP handles it):
// Route::group(['prefix' => 'api/v1', 'middleware' => ['api', 'auth:api']], function () {

Route::middleware(['auth:api']) // Ensure user is authenticated for these actions
    ->prefix(config('likeable.route_prefix', 'api/v1/likeable')) // Configurable prefix
    ->name('likeable.') // Route name prefix
    ->group(function () {
        // Route to like an item
        // POST /prefix/{likeable_type}/{likeable_id}/like
        Route::post('/{likeable_type}/{likeable_id}/like', [LikeController::class, 'like'])
            ->name('like')
            ->where('likeable_type', '[a-zA-Z0-9_]+') // Allow numbers for types like 'item1'
            ->where('likeable_id', '[0-9]+');

        // Route to unlike an item
        // DELETE /prefix/{likeable_type}/{likeable_id}/unlike
        Route::delete('/{likeable_type}/{likeable_id}/unlike', [LikeController::class, 'unlike'])
            ->name('unlike')
            ->where('likeable_type', '[a-zA-Z0-9_]+')
            ->where('likeable_id', '[0-9]+');

        // Route to toggle like status for an item
        // POST /prefix/{likeable_type}/{likeable_id}/toggle
        Route::post('/{likeable_type}/{likeable_id}/toggle', [LikeController::class, 'toggleLike'])
            ->name('toggle')
            ->where('likeable_type', '[a-zA-Z0-9_]+')
            ->where('likeable_id', '[0-9]+');
    });

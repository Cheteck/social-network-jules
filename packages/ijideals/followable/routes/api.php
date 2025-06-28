<?php

use Illuminate\Support\Facades\Route;
use Ijideals\Followable\Http\Controllers\FollowController;

// Assuming User model is the primary model to be followed,
// and its route key is 'id' or resolves correctly by Laravel.
// The {user} parameter will be route-model bound to the User model.

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('users/{user}/follow', [FollowController::class, 'follow'])->name('users.follow');
    Route::delete('users/{user}/unfollow', [FollowController::class, 'unfollow'])->name('users.unfollow');
    Route::post('users/{user}/toggle-follow', [FollowController::class, 'toggleFollow'])->name('users.togglefollow');
    Route::get('users/{user}/is-following', [FollowController::class, 'isFollowing'])->name('users.isfollowing');
    Route::get('users/{user}/followers', [FollowController::class, 'followers'])->name('users.followers');
    Route::get('users/{user}/followings', [FollowController::class, 'followings'])->name('users.followings');
});

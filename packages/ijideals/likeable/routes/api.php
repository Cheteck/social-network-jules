<?php

use Illuminate\Support\Facades\Route;
use Ijideals\Likeable\Http\Controllers\LikeController;
use Ijideals\SocialPosts\Models\Post; // Assuming Post model for likeable

// All routes here will be protected by 'auth:sanctum'
// and potentially prefixed with 'api' by the main app's RouteServiceProvider.
Route::middleware(['auth:sanctum'])->group(function () {

    // Like a Post
    // We use Route Model Binding for {post}. Laravel will resolve it to a Post instance.
    // The LikeableContract type-hint will be checked in the controller for safety.
    Route::post('posts/{post}/like', [LikeController::class, 'store'])
        ->name('posts.like')
        ->where('post', '[0-9]+'); // Ensure {post} is an integer ID for Post model

    // Unlike a Post
    Route::delete('posts/{post}/like', [LikeController::class, 'destroy'])
        ->name('posts.unlike')
        ->where('post', '[0-9]+');

    // Toggle like on a Post (alternative to separate like/unlike)
    // Route::post('posts/{post}/toggle-like', [LikeController::class, 'toggle'])
    //     ->name('posts.toggle-like')
    //     ->where('post', '[0-9]+');

    // Later, we can add more generic routes if needed, e.g.:
    // Route::post('{likeable_type}/{likeable_id}/like', [LikeController::class, 'storePolymorphic'])
    //    ->name('likeable.like');
    // Route::delete('{likeable_type}/{likeable_id}/like', [LikeController::class, 'destroyPolymorphic'])
    //    ->name('likeable.unlike');

});

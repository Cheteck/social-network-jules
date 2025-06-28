<?php

use Illuminate\Support\Facades\Route;
use Ijideals\HashtagSystem\Http\Controllers\HashtagController;

/*
|--------------------------------------------------------------------------
| Hashtag System API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->middleware('api')->group(function () {
    // Route to get all hashtags
    Route::get('/hashtags', [HashtagController::class, 'index'])->name('hashtags.index');

    // Route to get a single hashtag by slug
    Route::get('/hashtags/{slug}', [HashtagController::class, 'show'])->name('hashtags.show');

    // Route to get posts associated with a hashtag
    // This is a specific common case.
    Route::get('/hashtags/{slug}/posts', [HashtagController::class, 'getPostsByHashtag'])->name('hashtags.posts');

    // A more generic route to get items of a certain type associated with a hashtag
    // e.g., /api/hashtags/laravel/items/post or /api/hashtags/awesome/items/product
    Route::get('/hashtags/{slug}/items/{type}', [HashtagController::class, 'getItemsByHashtagAndType'])->name('hashtags.items.type');
});

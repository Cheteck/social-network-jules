<?php

use Illuminate\Support\Facades\Route;
use Ijideals\Commentable\Http\Controllers\CommentController;

Route::prefix(config('commentable.route_prefix', 'api/v1/comments'))
    ->name('comments.') // Route name prefix
    ->group(function () {
        // Get comments for a specific model
        // GET /prefix/{commentable_type}/{commentable_id}
        Route::get('/{commentable_type}/{commentable_id}', [CommentController::class, 'index'])
            ->name('index')
            ->where('commentable_type', '[a-zA-Z0-9_]+')
            ->where('commentable_id', '[0-9]+');

        // Post a new comment to a model
        // POST /prefix/{commentable_type}/{commentable_id}
        Route::post('/{commentable_type}/{commentable_id}', [CommentController::class, 'store'])
            ->middleware('auth:api') // Requires authentication
            ->name('store')
            ->where('commentable_type', '[a-zA-Z0-9_]+')
            ->where('commentable_id', '[0-9]+');

        // Update an existing comment
        // PUT /prefix/{comment_id} or PATCH /prefix/{comment_id}
        Route::match(['put', 'patch'], '/{comment_id}', [CommentController::class, 'update'])
            ->middleware('auth:api')
            ->name('update')
            ->where('comment_id', '[0-9]+');

        // Delete a comment
        // DELETE /prefix/{comment_id}
        Route::delete('/{comment_id}', [CommentController::class, 'destroy'])
            ->middleware('auth:api')
            ->name('destroy')
            ->where('comment_id', '[0-9]+');
    });

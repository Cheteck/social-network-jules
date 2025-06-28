<?php

use Illuminate\Support\Facades\Route;
use Ijideals\MediaUploader\Http\Controllers\MediaController;

Route::prefix(config('media-uploader.route_prefix', 'api/v1/media'))
    ->name('media.') // Route name prefix
    ->group(function () {

        // Get a specific media item by ID
        // GET /prefix/{media_id}
        Route::get('/{media_id}', [MediaController::class, 'show'])
            ->name('show')
            ->where('media_id', '[0-9]+');

        // Delete a specific media item by ID
        // DELETE /prefix/{media_id}
        Route::delete('/{media_id}', [MediaController::class, 'destroy'])
            ->middleware('auth:api')
            ->name('destroy')
            ->where('media_id', '[0-9]+');

        // Group routes by model type for clarity and specific model interactions
        Route::prefix('/model/{model_type_alias}/{model_id}')
            ->where(['model_type_alias' => '[a-zA-Z0-9_-]+', 'model_id' => '[0-9]+'])
            ->group(function () {
                // Upload media for a specific model
                // POST /prefix/model/{model_type_alias}/{model_id}
                // Body should contain 'file' (the uploaded file) and optionally 'collection_name'
                Route::post('/', [MediaController::class, 'storeForModel'])
                    ->middleware('auth:api')
                    ->name('storeForModel');

                // List media for a specific model and collection
                // GET /prefix/model/{model_type_alias}/{model_id}/collection/{collection_name?}
                Route::get('/collection/{collection_name?}', [MediaController::class, 'indexByModel'])
                    ->name('indexByModel');

                // Reorder media for a specific model and collection
                // POST /prefix/model/{model_type_alias}/{model_id}/collection/{collection_name}/reorder
                // Body should contain 'ordered_media_ids' (array of media IDs)
                Route::post('/collection/{collection_name}/reorder', [MediaController::class, 'reorder'])
                    ->middleware('auth:api')
                    ->name('reorder');
            });
    });

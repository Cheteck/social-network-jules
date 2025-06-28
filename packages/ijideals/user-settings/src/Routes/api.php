<?php

use Illuminate\Support\Facades\Route;
use Ijideals\UserSettings\Http\Controllers\UserSettingsController;

Route::prefix(config('user-settings.route_prefix', 'api/v1/user/settings'))
    ->middleware(['api', 'auth:api']) // All routes require authentication
    ->name('user.settings.') // Route name prefix
    ->group(function () {

        // Get user settings
        // GET /prefix/
        // Optional query param: ?keys=key1,key2.nestedkey
        Route::get('/', [UserSettingsController::class, 'index'])->name('index');

        // Update user settings
        // PUT /prefix/
        // Body should be an object of key-value pairs: { "key1": "value1", "key2.nested": true }
        Route::put('/', [UserSettingsController::class, 'update'])->name('update');

    });

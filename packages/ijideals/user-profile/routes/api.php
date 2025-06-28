<?php

use Illuminate\Support\Facades\Route;
use Ijideals\UserProfile\Http\Controllers\UserProfileController;

Route::middleware(['api'])->group(function () {
    // Publicly view a user's profile
    // Assumes User model is route model bound using its primary key.
    Route::get('users/{user}/profile', [UserProfileController::class, 'show'])->name('users.profile.show');

    Route::middleware(['auth:sanctum'])->group(function () {
        // Get the authenticated user's own profile
        Route::get('profile', [UserProfileController::class, 'current'])->name('profile.show');
        // Update the authenticated user's own profile
        Route::put('profile', [UserProfileController::class, 'update'])->name('profile.update');
    });
});

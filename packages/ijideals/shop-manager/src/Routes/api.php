<?php

use Illuminate\Support\Facades\Route;
use Ijideals\ShopManager\Http\Controllers\ShopController;
use Ijideals\ShopManager\Http\Controllers\ShopMemberController;

Route::prefix(config('shop-manager.route_prefix', 'api/v1/shops'))
    ->name('shops.') // Route name prefix
    ->group(function () {

        // Shop Management
        Route::get('/', [ShopController::class, 'index'])->name('index'); // List all active shops
        Route::post('/', [ShopController::class, 'store'])->middleware('auth:api')->name('store'); // Create a new shop

        Route::prefix('/{shopSlugOrId}')->group(function () {
            Route::get('/', [ShopController::class, 'show'])->name('show'); // Show a specific shop
            Route::put('/', [ShopController::class, 'update'])->middleware('auth:api')->name('update'); // Update a shop
            Route::patch('/', [ShopController::class, 'update'])->middleware('auth:api'); // Alias for update
            Route::delete('/', [ShopController::class, 'destroy'])->middleware('auth:api')->name('destroy'); // Delete a shop

            // Shop Member Management
            Route::prefix('/members')->name('members.')->middleware('auth:api')->group(function () {
                Route::get('/', [ShopMemberController::class, 'index'])->name('index'); // List shop members
                Route::post('/', [ShopMemberController::class, 'addMember'])->name('store'); // Add a member with a role

                Route::prefix('/{userId}')->where(['userId' => '[0-9]+'])->group(function () {
                    Route::put('/role', [ShopMemberController::class, 'updateMemberRole'])->name('updateRole'); // Update member's role
                    Route::patch('/role', [ShopMemberController::class, 'updateMemberRole']); // Alias
                    Route::delete('/', [ShopMemberController::class, 'removeMember'])->name('destroy'); // Remove a member
                });
            });

            // TODO: Routes for shop-specific content (posts, products)
            // Example: Route::apiResource('/posts', ShopPostController::class);

            // Shop Posts Management
            Route::prefix('/posts')->name('posts.')->group(function() {
                Route::get('/', [\Ijideals\ShopManager\Http\Controllers\ShopPostController::class, 'index'])->name('index');
                Route::post('/', [\Ijideals\ShopManager\Http\Controllers\ShopPostController::class, 'store'])->middleware('auth:api')->name('store');
                // TODO: Add routes for show, update, delete for shop posts if needed, with appropriate authorization
            });
        });
    });

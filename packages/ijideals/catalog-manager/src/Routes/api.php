<?php

use Illuminate\Support\Facades\Route;
use Ijideals\CatalogManager\Http\Controllers\CategoryController;
use Ijideals\CatalogManager\Http\Controllers\ProductController;
use Ijideals\CatalogManager\Http\Controllers\ProductOptionController;
use Ijideals\CatalogManager\Http\Controllers\ProductVariantController;

// --- Global Catalog Categories ---
Route::prefix(config('catalog-manager.route_prefixes.categories', 'api/v1/catalog/categories'))
    ->name('catalog.categories.')
    // ->middleware(['api', 'auth:api']) // Apply to all if all need auth, or per route
    ->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->middleware('auth:api')->name('store'); // Needs platform admin permission

        Route::prefix('/{categorySlugOrId}')->group(function() { // categorySlugOrId should be categoryId if using numeric IDs primarily
            Route::get('/', [CategoryController::class, 'show'])->name('show');
            Route::put('/', [CategoryController::class, 'update'])->middleware('auth:api')->name('update');
            Route::patch('/', [CategoryController::class, 'update'])->middleware('auth:api');
            Route::delete('/', [CategoryController::class, 'destroy'])->middleware('auth:api')->name('destroy');
        });
    });

// --- Global Product Options ---
Route::prefix(config('catalog-manager.route_prefixes.product_options', 'api/v1/catalog/product-options'))
    ->name('catalog.options.')
    ->middleware(['api', 'auth:api']) // Assuming all option management requires auth (platform admin)
    ->group(function () {
        Route::get('/', [ProductOptionController::class, 'index'])->name('index');
        Route::post('/', [ProductOptionController::class, 'store'])->name('store');
        Route::get('/{optionId}', [ProductOptionController::class, 'show'])->name('show')->where('optionId', '[0-9]+');
        Route::put('/{optionId}', [ProductOptionController::class, 'update'])->name('update')->where('optionId', '[0-9]+');
        Route::patch('/{optionId}', [ProductOptionController::class, 'update'])->where('optionId', '[0-9]+');
        Route::delete('/{optionId}', [ProductOptionController::class, 'destroy'])->name('destroy')->where('optionId', '[0-9]+');

        // Values for an option
        Route::prefix('/{optionId}/values')->name('values.')->where('optionId', '[0-9]+')->group(function () {
            Route::get('/', [ProductOptionController::class, 'indexValues'])->name('index');
            Route::post('/', [ProductOptionController::class, 'storeValue'])->name('store');
            Route::put('/{valueId}', [ProductOptionController::class, 'updateValue'])->name('update')->where('valueId', '[0-9]+');
            Route::patch('/{valueId}', [ProductOptionController::class, 'updateValue'])->where('valueId', '[0-9]+');
            Route::delete('/{valueId}', [ProductOptionController::class, 'destroyValue'])->name('destroy')->where('valueId', '[0-9]+');
        });
    });


// --- Shop-Specific Products ---
Route::prefix(config('catalog-manager.route_prefixes.shop_products', 'api/v1/shops/{shopSlugOrId}/products'))
    ->name('shops.products.')
    ->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index'); // Public
        Route::post('/', [ProductController::class, 'store'])->middleware('auth:api')->name('store'); // Shop admin/editor

        Route::prefix('/{productSlugOrId}')->group(function() {
            Route::get('/', [ProductController::class, 'show'])->name('show'); // Public
            Route::put('/', [ProductController::class, 'update'])->middleware('auth:api')->name('update');
            Route::patch('/', [ProductController::class, 'update'])->middleware('auth:api');
            Route::delete('/', [ProductController::class, 'destroy'])->middleware('auth:api')->name('destroy');

            // Product Options Association for a specific product
            Route::prefix('/options')->middleware('auth:api')->name('options.')->group(function() {
                Route::get('/', [ProductController::class, 'listProductOptions'])->name('list');
                Route::post('/', [ProductController::class, 'attachProductOption'])->name('attach'); // expects product_option_id
                Route::delete('/{optionId}', [ProductController::class, 'detachProductOption'])->name('detach')->where('optionId', '[0-9]+');
                Route::put('/', [ProductController::class, 'syncProductOptions'])->name('sync'); // expects option_ids array
            });

            // Product Variants for a specific product
            Route::prefix('/variants')->name('variants.')->group(function() {
                Route::get('/', [ProductVariantController::class, 'index'])->name('index'); // Public list of active variants
                Route::post('/', [ProductVariantController::class, 'store'])->middleware('auth:api')->name('store');
                Route::post('/generate', [ProductVariantController::class, 'generateVariants'])->middleware('auth:api')->name('generate');

                Route::prefix('/{variantId}')->where('variantId', '[0-9]+')->group(function() {
                    Route::get('/', [ProductVariantController::class, 'show'])->name('show'); // Public view of active variant
                    Route::put('/', [ProductVariantController::class, 'update'])->middleware('auth:api')->name('update');
                    Route::patch('/', [ProductVariantController::class, 'update'])->middleware('auth:api');
                    Route::delete('/', [ProductVariantController::class, 'destroy'])->middleware('auth:api')->name('destroy');
                });
            });
        });
    });

<?php

use Illuminate\Support\Facades\Route;
use Ijideals\CatalogManager\Http\Controllers\CategoryController;
use Ijideals\CatalogManager\Http\Controllers\ProductController;
use Ijideals\CatalogManager\Http\Controllers\ProductOptionController;
use Ijideals\CatalogManager\Http\Controllers\ProductVariantController;

// --- Global Catalog Categories ---
Route::group([
    'prefix' => config('catalog-manager.route_prefixes.categories', 'api/v1/catalog/categories'),
    'as' => 'catalog.categories.',
    // 'middleware' => ['api', 'auth:api'] // Apply to all if all need auth, or per route
], function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->middleware('auth:api')->name('store'); // Needs platform admin permission

    Route::group(['prefix' => '/{categorySlugOrId}'], function() { // categorySlugOrId should be categoryId if using numeric IDs primarily
        Route::get('/', [CategoryController::class, 'show'])->name('show');
        Route::put('/', [CategoryController::class, 'update'])->middleware('auth:api')->name('update');
        Route::patch('/', [CategoryController::class, 'update'])->middleware('auth:api'); // Note: duplicate name 'update' for category
        Route::delete('/', [CategoryController::class, 'destroy'])->middleware('auth:api')->name('destroy');
    });
});

// --- Global Product Options ---
Route::group([
    'prefix' => config('catalog-manager.route_prefixes.product_options', 'api/v1/catalog/product-options'),
    'as' => 'catalog.options.',
    'middleware' => ['api', 'auth:api'] // Assuming all option management requires auth (platform admin)
], function () {
    Route::get('/', [ProductOptionController::class, 'index'])->name('index');
    Route::post('/', [ProductOptionController::class, 'store'])->name('store');
    Route::get('/{optionId}', [ProductOptionController::class, 'show'])->name('show')->where('optionId', '[0-9]+');
    Route::put('/{optionId}', [ProductOptionController::class, 'update'])->name('update')->where('optionId', '[0-9]+');
    Route::patch('/{optionId}', [ProductOptionController::class, 'update'])->name('update')->where('optionId', '[0-9]+'); // Note: duplicate name 'update' for option
    Route::delete('/{optionId}', [ProductOptionController::class, 'destroy'])->name('destroy')->where('optionId', '[0-9]+');

    // Values for an option
    Route::group(['prefix' => '/{optionId}/values', 'as' => 'values.', 'where' => ['optionId' => '[0-9]+']], function () {
        Route::get('/', [ProductOptionController::class, 'indexValues'])->name('index');
        Route::post('/', [ProductOptionController::class, 'storeValue'])->name('store');
        Route::put('/{valueId}', [ProductOptionController::class, 'updateValue'])->name('update')->where('valueId', '[0-9]+');
        Route::patch('/{valueId}', [ProductOptionController::class, 'updateValue'])->name('update')->where('valueId', '[0-9]+'); // Note: duplicate name 'update' for value
        Route::delete('/{valueId}', [ProductOptionController::class, 'destroyValue'])->name('destroy')->where('valueId', '[0-9]+');
    });
});


// --- Shop-Specific Products ---
Route::group([
    'prefix' => config('catalog-manager.route_prefixes.shop_products', 'api/v1/shops/{shopSlugOrId}/products'),
    'as' => 'shops.products.'
], function () {
    Route::get('/', [ProductController::class, 'index'])->name('index'); // Public
    Route::post('/', [ProductController::class, 'store'])->middleware('auth:api')->name('store'); // Shop admin/editor

    Route::group(['prefix' => '/{productSlugOrId}'], function() {
        Route::get('/', [ProductController::class, 'show'])->name('show'); // Public
        Route::put('/', [ProductController::class, 'update'])->middleware('auth:api')->name('update');
        Route::patch('/', [ProductController::class, 'update'])->middleware('auth:api'); // Note: duplicate name 'update' for product
        Route::delete('/', [ProductController::class, 'destroy'])->middleware('auth:api')->name('destroy');

        // Product Options Association for a specific product
        Route::group(['prefix' => '/options', 'as' => 'options.', 'middleware' => 'auth:api'], function() {
            Route::get('/', [ProductController::class, 'listProductOptions'])->name('list');
            Route::post('/', [ProductController::class, 'attachProductOption'])->name('attach'); // expects product_option_id
            Route::delete('/{optionId}', [ProductController::class, 'detachProductOption'])->name('detach')->where('optionId', '[0-9]+');
            Route::put('/', [ProductController::class, 'syncProductOptions'])->name('sync'); // expects option_ids array
        });

        // Product Variants for a specific product
        Route::group(['prefix' => '/variants', 'as' => 'variants.'], function() {
            Route::get('/', [ProductVariantController::class, 'index'])->name('index'); // Public list of active variants
            Route::post('/', [ProductVariantController::class, 'store'])->middleware('auth:api')->name('store');
            Route::post('/generate', [ProductVariantController::class, 'generateVariants'])->middleware('auth:api')->name('generate');

            Route::group(['prefix' => '/{variantId}', 'where' => ['variantId' => '[0-9]+']], function() {
                Route::get('/', [ProductVariantController::class, 'show'])->name('show'); // Public view of active variant
                Route::put('/', [ProductVariantController::class, 'update'])->middleware('auth:api')->name('update');
                Route::patch('/', [ProductVariantController::class, 'update'])->middleware('auth:api'); // Note: duplicate name 'update' for variant
                Route::delete('/', [ProductVariantController::class, 'destroy'])->middleware('auth:api')->name('destroy');
            });
        });
    });
});

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    | Used for associating product reviews or other user-related catalog actions in the future.
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Shop Model
    |--------------------------------------------------------------------------
    | The Eloquent model for Shops, which own products.
    */
    'shop_model' => \Ijideals\ShopManager\Models\Shop::class,

    /*
    |--------------------------------------------------------------------------
    | Product Model
    |--------------------------------------------------------------------------
    */
    'product_model' => \Ijideals\CatalogManager\Models\Product::class,

    /*
    |--------------------------------------------------------------------------
    | Category Model
    |--------------------------------------------------------------------------
    */
    'category_model' => \Ijideals\CatalogManager\Models\Category::class,

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'products' => 'products',
        'categories' => 'categories',
        'category_product' => 'category_product', // Pivot table
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Collections for Products (using ijideals/media-uploader)
    |--------------------------------------------------------------------------
    */
    'media_collections' => [
        'product_images' => [
            'name' => 'product_images', // Collection name for Media Uploader
            'single_file' => false, // Products can have multiple images
            // Add other specific Media Uploader settings here if needed
            // 'disk' => 'public',
            // 'directory' => 'products/{model_id}/images',
            // 'max_file_size' => 2048, // KB
            // 'allowed_mime_types' => ['image/jpeg', 'image/png'],
        ],
        'product_variant_images' => [
            'name' => 'product_variant_images',
            'single_file' => false, // A variant might have multiple angle shots
            // 'directory' => 'products/{product_id}/variants/{model_id}/images', // model_id here is variant_id
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Slug Generation
    |--------------------------------------------------------------------------
    | Field to use for generating slugs for products.
    */
    'product_slug_source_field' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Category Slug Generation
    |--------------------------------------------------------------------------
    | Field to use for generating slugs for categories.
    */
    'category_slug_source_field' => 'name',

    /*
    |--------------------------------------------------------------------------
    | API Route Prefixes
    |--------------------------------------------------------------------------
    */
    'route_prefixes' => [
        'categories' => 'api/v1/catalog/categories', // For global categories
        'shop_products' => 'api/v1/shops/{shopSlugOrId}/products', // For shop-specific products
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Items
    |--------------------------------------------------------------------------
    */
    'pagination_items' => [
        'products' => 15,
        'categories' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock Management
    |--------------------------------------------------------------------------
    | Enable/disable stock management features. If enabled, product creation/update
    | will expect 'stock_quantity'.
    */
    'stock_management_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Product Variants (Future placeholder)
    |--------------------------------------------------------------------------
    | Configuration for product variants (e.g., size, color).
    | 'enabled' => false for MVP.
    */
    'variants_enabled' => false,

];

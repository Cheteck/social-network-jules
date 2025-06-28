<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Searchable Models
    |--------------------------------------------------------------------------
    |
    | Define the Eloquent models that should be searchable.
    | The key is a short alias for the model (used in API requests, e.g., 'users', 'posts'),
    | and the value is the fully qualified class name of the model.
    | These models must use the Laravel\Scout\Searchable trait.
    |
    */
    'searchable_models' => [
        'user' => \App\Models\User::class,
        'post' => \Ijideals\SocialPosts\Models\Post::class,
        'shop' => \Ijideals\ShopManager\Models\Shop::class,
        'product' => \Ijideals\CatalogManager\Models\Product::class, // Added Product
        // 'comment' => \Ijideals\Commentable\Models\Comment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Searchable Fields Per Model
    |--------------------------------------------------------------------------
    |
    | For models where toSearchableArray() might be simple or you want a
    | fallback, you can define default fields to be included in the search index.
    | This is more of a convention, as toSearchableArray() in the model
    | ultimately controls what's indexed. This array is not directly used by
    | Scout but can be a reference for your `toSearchableArray` implementations.
    |
    */
    'default_searchable_fields' => [
        \App\Models\User::class => ['name', 'email'],
        \Ijideals\SocialPosts\Models\Post::class => ['content'],
        \Ijideals\ShopManager\Models\Shop::class => ['name', 'description'],
        \Ijideals\CatalogManager\Models\Product::class => ['name', 'description', 'sku'],
        // \Ijideals\Commentable\Models\Comment::class => ['content'],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/v1/search',

    /*
    |--------------------------------------------------------------------------
    | Default Pagination Items
    |--------------------------------------------------------------------------
    |
    | Number of items to show per page for each model type in search results.
    |
    */
    'pagination_items' => 10,

    /*
    |--------------------------------------------------------------------------
    | Scout Driver Specific Settings
    |--------------------------------------------------------------------------
    |
    | If using a specific Scout driver that requires additional configuration
    | at the package level, you can add it here. For the 'database' driver,
    | most configuration is in config/scout.php.
    |
    */
    'scout_settings' => [
        // 'database' => [
        //     'connection' => null, // Uses default DB connection
        // ],
    ],
];

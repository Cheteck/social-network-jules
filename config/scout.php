<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search engine that will be used when
    | performing searches against your models. Driver specific configuration
    | options are defined below supported engines, models, and classes.
    |
    | Supported: "algolia", "meilisearch", "database", "collection", null
    |
    */

    'driver' => env('SCOUT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | applications sharing the same search engine instances.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing operations will be queued for processing.
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data syncing operations
    | should be performed within a database transaction. This provides
    | greater data integrity without sacrificing performance.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options control the chunk sizes used when importing and deleting
    | data syncing operations. These operations are chunked after determining
    | the chunk size based on the engine configuration.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful if you
    | want to search soft deleted records specifically.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to an identified user will be
    | synced with Meilisearch and Algolia when using the Searchable Trait.
    |
    |
    */
    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configuration your Algolia settings. Algolia is a cloud
    | search engine which provides powerful search options for Laravel
    | Scout providing powerful locations search and other great algos.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configuration your Meilisearch settings. Meilisearch is an
    | open source search engine with minimal configuration. Below, you may
    | configure your host and key information for the Meilisearch servers.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration#master-key
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // \App\Models\Post::class => [
            //     'filterableAttributes'=> ['author', 'tags'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the database driver options. By default, it will
    | use the default database connection. However, you can provide a
    | connection name here to use a specific database connection.
    |
    */

    'database' => [
        'table' => env('SCOUT_DATABASE_TABLE', 'scout_index'),
        'connection' => env('SCOUT_DATABASE_CONNECTION'), // null uses default
    ],
];

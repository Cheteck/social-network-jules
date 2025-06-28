<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class that represents your users. This model should
    | use the Followable trait from ijideals/followable if you want to
    | generate feeds based on followed users.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Post Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class that represents posts or content items.
    | This model should be the one managed by ijideals/social-posts.
    |
    */
    'post_model' => \Ijideals\SocialPosts\Models\Post::class,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | - store: The cache store to use (null for default Laravel cache store).
    | - prefix: A prefix for all cache keys related to the news feed.
    | - ttl: Time-to-live for cached feeds in minutes.
    |
    */
    'cache' => [
        'store' => null, // null for default, or specify a store like 'redis', 'memcached'
        'prefix' => 'news_feed',
        'ttl' => env('NEWS_FEED_CACHE_TTL_MINUTES', 60), // Cache TTL in minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Feed Generation Batch Size
    |--------------------------------------------------------------------------
    |
    | When fetching posts from followed users or other sources, this determines
    | how many posts to retrieve per source/batch. Helps manage memory for
    | users following many active people.
    |
    */
    'batch_size' => 100,

    /*
    |--------------------------------------------------------------------------
    | Default Pagination Items
    |--------------------------------------------------------------------------
    |
    | Number of items to show per page in the feed API response.
    |
    */
    'pagination_items' => 15,

    /*
    |--------------------------------------------------------------------------
    | Ranking Algorithm Configuration (Initial - for future use)
    |--------------------------------------------------------------------------
    |
    | - default_sort: 'created_at' (for chronological) or 'score' (for ranked).
    | - factors_weights: Weights for different scoring factors (e.g., recency, likes).
    |   Example: 'recency' => 0.5, 'likes' => 0.3, 'comments' => 0.2
    |
    */
    'ranking' => [
        'default_sort_column' => 'created_at', // 'score' once ranking engine is more developed
        'default_sort_direction' => 'desc',
        'factors_weights' => [
            'recency' => 1.0, // Initially, only recency matters for MVP
            // 'engagement_likes' => 0,
            // 'engagement_comments' => 0,
            // 'affinity_with_author' => 0,
        ],
        // Time decay parameters for recency scoring (e.g., half-life in hours)
        // 'recency_score_half_life_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Settings (Initial - for future use)
    |--------------------------------------------------------------------------
    |
    | - enabled: Whether to include "discovery" content from non-followed users.
    | - max_items: Max number of discovery items to try and inject.
    | - sources: Configuration for different discovery algorithms.
    |
    */
    'discovery' => [
        'enabled' => false, // Disabled for MVP
        'max_items_ratio' => 0.2, // e.g., 20% of feed items could be discovery
        // 'sources' => [
        //     'popular_global' => ['weight' => 0.5, 'min_likes' => 10],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/v1/feed',

];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the User model that will be used to create relationships.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Like Model
    |--------------------------------------------------------------------------
    |
    | This is the Like model that will be used to store likes.
    |
    */
    'like_model' => \Ijideals\Likeable\Models\Like::class,

    /*
    |--------------------------------------------------------------------------
    | Likes Table Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the database table that will store likes.
    |
    */
    'table_name' => 'likes',

    /*
    |--------------------------------------------------------------------------
    | Event Classes
    |--------------------------------------------------------------------------
    |
    | Specify the event classes that are dispatched when a model is liked
    | or unliked. You can use your own custom event classes if needed.
    | Set to null to disable dispatching for a specific event.
    |
    */
    'events' => [
        'model_liked' => \Ijideals\Likeable\Events\ModelLiked::class,
        // 'model_unliked' => \Ijideals\Likeable\Events\ModelUnliked::class, // Example for unliking
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph Map
    |--------------------------------------------------------------------------
    |
    | Define the morph map aliases for likeable models. This helps keep your
    | database clean and readable.
    | Example: 'post' => \App\Models\Post::class,
    |
    */
    'morph_map' => [
        // 'post' => 'App\\Models\\Post', // Example
    ],
];

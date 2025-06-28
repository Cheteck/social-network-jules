<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Specify the user model class. This is typically App\Models\User.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Comment Model
    |--------------------------------------------------------------------------
    |
    | Specify the comment model class.
    |
    */
    'comment_model' => \Ijideals\Commentable\Models\Comment::class,

    /*
    |--------------------------------------------------------------------------
    | Comments Table Name
    |--------------------------------------------------------------------------
    |
    | Specify the name of the database table that will store comments.
    |
    */
    'table_name' => 'comments',

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | Define if comments should use soft deletes. If true, a 'deleted_at'
    | column will be added to the comments table.
    |
    */
    'soft_deletes' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Comment Order
    |--------------------------------------------------------------------------
    |
    | Define the default order for comments. 'asc' for oldest first,
    | 'desc' for newest first.
    |
    */
    'default_order' => 'asc', // 'desc' for newest first

    /*
    |--------------------------------------------------------------------------
    | Nested Comments / Replies
    |--------------------------------------------------------------------------
    |
    | Enable or disable nested comments (replies to comments).
    | If enabled, a 'parent_id' column will be added to the comments table.
    | 'max_depth' defines how many levels of replies are allowed (0 for infinite).
    |
    */
    'nested_comments' => true,
    'max_depth' => 3, // 0 for infinite depth

    /*
    |--------------------------------------------------------------------------
    | Commentable Types (Morph Map)
    |--------------------------------------------------------------------------
    |
    | Define the morph map aliases for commentable models. This helps keep your
    | database clean and readable.
    | Example:
    | 'post' => \App\Models\Post::class,
    |
    */
    'morph_map' => [
        // 'post' => 'App\\Models\\Post', // Example
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix
    |--------------------------------------------------------------------------
    |
    | Define the prefix for the API routes of this package.
    |
    */
    'route_prefix' => 'api/v1/comments',

    /*
    |--------------------------------------------------------------------------
    | Event Broadcasting
    |--------------------------------------------------------------------------
    |
    | Define if events like CommentPosted, CommentUpdated, CommentDeleted
    | should be broadcasted.
    |
    */
    'broadcast_events' => false,
    /*
    |--------------------------------------------------------------------------
    | Event Classes
    |--------------------------------------------------------------------------
    |
    | Specify the event classes dispatched by the package.
    | Set to null to disable dispatching for a specific event.
    |
    */
    'events' => [
        'comment_posted' => \Ijideals\Commentable\Events\CommentPosted::class,
        // 'comment_updated' => \Ijideals\Commentable\Events\CommentUpdated::class,
        // 'comment_deleted' => \Ijideals\Commentable\Events\CommentDeleted::class,
    ],
];

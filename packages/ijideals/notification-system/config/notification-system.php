<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class that represents your users who receive notifications.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Notification Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class that represents stored notifications.
    |
    */
    'notification_model' => \Ijideals\NotificationSystem\Models\Notification::class,

    /*
    |--------------------------------------------------------------------------
    | Notifications Table Name
    |--------------------------------------------------------------------------
    */
    'table_name' => 'notifications',

    /*
    |--------------------------------------------------------------------------
    | Default Pagination Items
    |--------------------------------------------------------------------------
    |
    | Number of notifications to show per page in the API response.
    |
    */
    'pagination_items' => 20,

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/v1/notifications',

    /*
    |--------------------------------------------------------------------------
    | Event Listeners Mapping
    |--------------------------------------------------------------------------
    |
    | Define which events should trigger which notification listeners.
    | This allows the notification system to react to events from other packages
    | in a decoupled manner.
    |
    | Example:
    | 'Ijideals\Likeable\Events\ModelLiked' => [
    |     'Ijideals\NotificationSystem\Listeners\SendNewLikeNotificationListener',
    | ],
    | 'Ijideals\Commentable\Events\CommentPosted' => [
    |     'Ijideals\NotificationSystem\Listeners\SendNewCommentNotificationListener',
    | ],
    | 'Ijideals\Followable\Events\UserFollowed' => [ // Assuming Followable package fires this
    |     'Ijideals\NotificationSystem\Listeners\SendNewFollowerNotificationListener',
    | ],
    |
    */
    'event_listeners' => [
        \Ijideals\Likeable\Events\ModelLiked::class => [
            \Ijideals\NotificationSystem\Listeners\SendNewLikeNotificationListener::class,
        ],
        \Ijideals\Commentable\Events\CommentPosted::class => [
            \Ijideals\NotificationSystem\Listeners\SendNewCommentNotificationListener::class,
        ],
        \Ijideals\Followable\Events\UserFollowed::class => [
            \Ijideals\NotificationSystem\Listeners\SendNewFollowerNotificationListener::class,
        ],
        // We don't have a specific listener for UserUnfollowed yet, but it's good practice
        // to have the event available if other parts of the system might need it.
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define specific settings or handlers for different notification types.
    | This can be used for grouping, formatting, or specific logic per type.
    | The 'key' is the string that will be stored in the 'type' column of the notifications table.
    |
    | Example:
    | 'new_like' => [
    |     'message_template' => '{actor.name} liked your {likeable_type}.', // Basic template idea
    |     'groupable' => true, // If multiple likes from same user on same item can be grouped
    |     'default_channels' => ['database'], // if using Laravel's notification channels later
    | ],
    | 'new_comment' => [
    |     'message_template' => '{actor.name} commented on your {commentable_type}.',
    | ],
    | 'new_follower' => [
    |    'message_template' => '{actor.name} started following you.',
    | ],
    */
    'notification_types' => [
        'new_like' => [
            'description' => 'A user liked one of your items.',
        ],
        'new_comment' => [
            'description' => 'A user commented on one of your items.',
        ],
        'new_reply' => [
            'description' => 'A user replied to one of your comments.',
        ],
        'new_follower' => [
            'description' => 'A user started following you.',
        ],
        // Add more types as needed
    ],

];

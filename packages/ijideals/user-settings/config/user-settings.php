<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UserSetting Model
    |--------------------------------------------------------------------------
    */
    'usersetting_model' => \Ijideals\UserSettings\Models\UserSetting::class,

    /*
    |--------------------------------------------------------------------------
    | User Settings Table Name
    |--------------------------------------------------------------------------
    */
    'table_name' => 'user_settings',

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/v1/user/settings',

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Define all available user settings here with their default values.
    | This also serves as a list of allowed keys for settings.
    | The structure can be nested using dot notation for keys.
    | Values can be boolean, string, integer, etc. They will be stored as strings
    | or JSON in the database, so ensure your application logic can handle casting.
    |
    */
    'defaults' => [
        'notifications' => [
            'new_like' => [
                'database' => true, // Receive notification in DB (on-site)
                // 'email' => false, // Future: Receive email notification
                // 'push' => false,  // Future: Receive push notification
            ],
            'new_comment' => [
                'database' => true,
                // 'email' => false,
            ],
            'new_reply' => [ // Notification for a reply to user's comment
                'database' => true,
                // 'email' => false,
            ],
            'new_follower' => [
                'database' => true,
                // 'email' => true,
            ],
            // Example for shop-related notifications if user owns/manages a shop
            // 'shop_new_order' => [ // If an e-commerce module is added
            //     'database' => true,
            //     'email' => true,
            // ],
        ],
        'privacy' => [
            // 'profile_visibility' => 'public', // 'public', 'followers_only', 'private'
            // 'show_email_on_profile' => false,
            // 'allow_direct_messages_from' => 'everyone', // 'everyone', 'followers', 'none'
        ],
        'feed' => [
            // 'show_reposts' => true,
            // 'content_density' => 'normal', // 'compact', 'normal', 'comfortable'
        ],
        // Add more categories and settings as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Casts for Settings Values
    |--------------------------------------------------------------------------
    |
    | Define how specific setting values should be cast when retrieved.
    | This is useful if you store booleans as '1'/'0' or 'true'/'false' strings.
    | The HasSettings trait will attempt to use these casts.
    | Supported: 'boolean', 'integer', 'string', 'array', 'object', 'float', 'double'
    |
    */
    'casts' => [
        'notifications.new_like.database' => 'boolean',
        'notifications.new_comment.database' => 'boolean',
        'notifications.new_reply.database' => 'boolean',
        'notifications.new_follower.database' => 'boolean',
        // 'privacy.show_email_on_profile' => 'boolean',
    ],
];

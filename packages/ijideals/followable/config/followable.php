<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that represents users in your application.
    | This model should use the Followable trait if users can follow other users.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Followers Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table used to store follower relationships.
    |
    */
    'followers_table' => 'followers',

    /*
    |--------------------------------------------------------------------------
    | Event Classes
    |--------------------------------------------------------------------------
    |
    | Specify the event classes that are dispatched when a user follows or
    | unfollows a model. You can use your own custom event classes if needed.
    | Set to null to disable dispatching for a specific event.
    |
    */
    'events' => [
        'user_followed' => \Ijideals\Followable\Events\UserFollowed::class,
        'user_unfollowed' => \Ijideals\Followable\Events\UserUnfollowed::class,
    ],
];

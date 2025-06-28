<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class that represents your users. This model will
    | be used for shop ownership and membership. It should use the
    | Spatie\Permission\Traits\HasRoles trait.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Shop Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class that represents your shops/business pages.
    |
    */
    'shop_model' => \Ijideals\ShopManager\Models\Shop::class,

    /*
    |--------------------------------------------------------------------------
    | Shops Table Name
    |--------------------------------------------------------------------------
    */
    'shops_table' => 'shops',

    /*
    |--------------------------------------------------------------------------
    | Post Model (for shop posts)
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class for posts, if shops can create posts.
    | This should be the model from ijideals/social-posts.
    |
    */
    'post_model_class' => \Ijideals\SocialPosts\Models\Post::class,

    /*
    |--------------------------------------------------------------------------
    | Shop User Pivot Table Name (for members, if not using Spatie's team roles directly for membership tracking)
    |--------------------------------------------------------------------------
    | This table could store additional membership details if needed, beyond what
    | spatie/laravel-permission's model_has_roles with team_id provides.
    | For simple role assignment per shop, Spatie's tables might be sufficient.
    */
    // 'shop_user_table' => 'shop_user',


    /*
    |--------------------------------------------------------------------------
    | Spatie Permission Integration
    |--------------------------------------------------------------------------
    |
    | Settings related to spatie/laravel-permission.
    | The 'teams' feature must be enabled in config/permission.php,
    | and 'team_foreign_key' set to 'shop_id'.
    |
    */
    'permission' => [
        // Define default roles created for each new shop, or global shop-related roles
        // These are just names; actual permissions are assigned via Spatie's mechanisms.
        // The first role is typically assigned to the shop owner upon creation.
        'default_shop_roles' => [
            'shop_owner',      // Owner, full control over their shop
            'shop_admin',      // Can manage most aspects, assigned by owner
            'shop_editor',     // Can manage content (posts, products)
            'shop_moderator',  // Can moderate comments/content within the shop
            'shop_viewer',     // Can view shop-specific backend/analytics
        ],
        // Define global platform roles that have broad shop management capabilities
        'platform_shop_admin_roles' => [
            'platform_admin',
            'platform_superadmin',
            // 'platform_shops_manager' // Another example
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Collections for Shops (using ijideals/media-uploader)
    |--------------------------------------------------------------------------
    */
    'media_collections' => [
        'shop_logo' => [
            'name' => 'shop_logo', // Collection name for Media Uploader
            'single_file' => true,
            // Add other specific Media Uploader settings here if needed
            // 'disk' => 'public',
            // 'directory' => 'shops/{model_id}/logos',
            // 'max_file_size' => 1024, // KB
            // 'allowed_mime_types' => ['image/jpeg', 'image/png'],
        ],
        'shop_cover_image' => [
            'name' => 'shop_cover_image',
            'single_file' => true,
            // 'directory' => 'shops/{model_id}/covers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/v1/shops',

    /*
    |--------------------------------------------------------------------------
    | Slug Generation
    |--------------------------------------------------------------------------
    | Field to use for generating slugs for shops.
    */
    'slug_source_field' => 'name',

];

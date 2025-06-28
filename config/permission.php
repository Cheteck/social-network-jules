<?php

return [

    'models' => [

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Spatie\Permission\Contracts\Permission` contract.
         */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Spatie\Permission\Contracts\Role` contract.
         */

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your model's permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your model's roles. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_roles' => 'model_has_roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your role's permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         *
         * For example, this would be nice if your primary keys are all UUIDs. In
         * that case, name this `model_uuid`.
         */

        'model_morph_key' => 'model_id',

        /*
         * Change this if you want to use a different foreign key column name for the
         * `team_id` provided by the `HasRoles` trait.
         *
         * Note: `teams` must be true in this config file for this to have any effect.
         */
        'team_foreign_key' => 'shop_id', // Changed to 'shop_id'
    ],

    /*
     * When set to true, the package will register `permission` and `role` middleware
     * with the applications's router.
     */
    'register_middleware' => true,

    /*
     * When set to true, this package will add a `hasPermissionTo` method to the
     * Gate facade an example usage would be Gate::hasPermissionTo('view-posts').
     */
    'register_permission_check_method' => true,

    /*
     * By default, wildcard permission lookups are disabled.
     * See https://spatie.be/docs/laravel-permission/v5/basic-usage/wildcard-permissions
     *
     * Wildcard permissions are powerful but also potentially dangerous if not implemented carefully.
     * Only use them if you understand the implications.
     */
    'enable_wildcard_permission' => false,

    /*
     * When set to true, the package will use Laravel's built-in cache caching
     * mechanisms to cache permissions and roles. By default, the permission
     * and role cache will be flushed automatically whenever permissions or roles
     * are created, updated, or deleted.
     */
    'cache' => [
        /*
         * By default all permissions are cached for 24 hours unless a permission or
         * role is updated. Then the cache is flushed immediately.
         */
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),

        /*
         * The cache key of the permissions cache.
         */
        'key' => 'spatie.permission.cache',

        /*
         * When checking for a permission against a model instead of a specific guard,
         * the cache key should include the model type and model ID.
         *
         * This avoids issues when checking permissions across different models of the same type.
         */
        'model_key' => 'spatie.permission.cache.model',

        /*
         * You may optionally indicate a specific cache driver to use for permission and
         * role caching using any of a valid Laravel cache store.
         */
        'store' => 'default',
    ],

    /*
     * When set to true, the "teams" feature is enabled.
     * With teams enabled, model_has_roles and model_has_permissions will have a team_id column.
     * Roles and permissions are still global, but their assignment to a user can be team-specific.
     */
    'teams' => true, // Enabled teams feature

    /*
     * When the "teams" feature is enabled, this defines whether the team_foreign_key on the
     * model_has_roles and model_has_permissions tables can be null (for global roles)
     * or must always be filled (roles/permissions are always team-specific).
     */
    'teams_foreign_key_null_when_no_team' => true, // Allows global roles by having shop_id = null
];

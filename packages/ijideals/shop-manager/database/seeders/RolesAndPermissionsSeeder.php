<?php

namespace Ijideals\ShopManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define Permission Guard Name (e.g., 'api' if your users authenticate via API for these actions)
        $guardName = config('auth.defaults.guard'); // Or 'api' or a custom guard

        // == PLATFORM PERMISSIONS & ROLES (shop_id IS NULL) ==
        $platformPermissions = [
            'manage_platform_settings',
            'manage_all_users',
            'manage_all_shops', // Can view/edit/delete any shop from platform level
            'access_platform_admin_panel',
        ];
        foreach ($platformPermissions as $permission) {
            Permission::findOrCreate($permission, $guardName);
        }

        // Platform Admin Role (Global)
        $platformAdminRole = Role::findOrCreate('platform_admin', $guardName);
        $platformAdminRole->givePermissionTo($platformPermissions); // Assign existing platform permissions

        // Platform Super Admin Role (Global) - inherits all permissions
        $platformSuperAdminRole = Role::findOrCreate('platform_superadmin', $guardName);
        // In Spatie, a common way to give all permissions is to not assign specific ones,
        // and then use a Gate::before() check for this role, or assign all known permissions.
        // For simplicity, let's assign all currently defined platform and shop permissions to superadmin.
        // Note: This means any new permission needs to be added here or handled by Gate::before().
        $allCurrentPermissions = array_merge($platformPermissions, $shopPermissions);
        foreach ($allCurrentPermissions as $permission) {
             Permission::findOrCreate($permission, $guardName); // Ensure they exist
        }
        $platformSuperAdminRole->givePermissionTo(Permission::whereIn('name', $allCurrentPermissions)->where('guard_name', $guardName)->get());


        // Platform Content Moderator Role (Global)
        // $platformModeratorRole = Role::findOrCreate('platform_content_moderator', $guardName);
        // $platformModeratorRole->givePermissionTo(['moderate_any_content']); // Example


        // == SHOP-SPECIFIC PERMISSIONS & ROLES (These permissions will be assigned with a shop_id context) ==
        // These permissions are defined globally but their assignment to a user will be scoped to a team (shop).
        $shopPermissions = [
            // Shop management
            'view_shop_dashboard',
            'edit_shop_settings',    // Edit shop name, description, logo, cover etc.
            'delete_shop',           // Delete the shop itself (only owner or platform admin usually)

            // Member management for the shop
            'manage_shop_members',   // Invite, remove, change roles of members within this shop

            // Content management for the shop
            'create_shop_posts',
            'edit_own_shop_posts',
            'edit_any_shop_posts',
            'delete_own_shop_posts',
            'delete_any_shop_posts',
            'publish_shop_posts',

            // Product management for the shop (example for future)
            // 'manage_shop_products',
            // 'view_shop_orders',
        ];
        foreach ($shopPermissions as $permission) {
            Permission::findOrCreate($permission, $guardName);
        }

        // Shop Admin Role (to be assigned per shop)
        $shopAdminRole = Role::findOrCreate('shop_admin', $guardName);
        $shopAdminRole->givePermissionTo([
            'view_shop_dashboard', 'edit_shop_settings',
            'manage_shop_members',
            'create_shop_posts', 'edit_any_shop_posts', 'delete_any_shop_posts', 'publish_shop_posts',
            // 'manage_shop_products', 'view_shop_orders',
            // Note: 'delete_shop' might be reserved for the original owner or platform_admin
        ]);

        // Shop Owner Role (to be assigned per shop - typically has all shop_admin permissions + delete_shop)
        $shopOwnerRole = Role::findOrCreate('shop_owner', $guardName);
        $shopOwnerRole->givePermissionTo([
            'view_shop_dashboard', 'edit_shop_settings', 'delete_shop', // Owner can delete their shop
            'manage_shop_members',
            'create_shop_posts', 'edit_any_shop_posts', 'delete_any_shop_posts', 'publish_shop_posts',
            // 'manage_shop_products', 'view_shop_orders',
        ]);

        // Shop Editor Role (to be assigned per shop)
        $shopEditorRole = Role::findOrCreate('shop_editor', $guardName);
        $shopEditorRole->givePermissionTo([
            'view_shop_dashboard',
            'create_shop_posts', 'edit_own_shop_posts', 'delete_own_shop_posts', 'publish_shop_posts',
            // Potentially some product management permissions
        ]);

        // Shop Viewer Role (to be assigned per shop)
        $shopViewerRole = Role::findOrCreate('shop_viewer', $guardName);
        $shopViewerRole->givePermissionTo(['view_shop_dashboard']);


        // --- Assigning a Platform Admin (Example - typically done in a different seeder or manually) ---
        // $userModelClass = config('shop-manager.user_model', \App\Models\User::class);
        // $adminUser = $userModelClass::where('email', 'admin@example.com')->first();
        // if ($adminUser) {
        //    // For global role, teamId is null when 'teams_foreign_key_null_when_no_team' is true
        //    $adminUser->assignRole($platformAdminRole);
        // }
    }
}

<?php

namespace Ijideals\ShopManager\Policies;

use App\Models\User; // Assuming main app User model
use Ijideals\ShopManager\Models\Shop;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShopPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * All users (including guests) can attempt to list shops.
     * Controller will filter for active ones for guests.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Guests can view active shops. Authenticated users with specific roles or ownership can view inactive ones.
     */
    public function view(?User $user, Shop $shop): bool
    {
        if ($shop->is_active) {
            return true;
        }
        // Allow viewing inactive shops only for owner, shop admins, or platform admins
        if ($user) {
            return $user->id === $shop->owner_id ||
                   $user->hasShopRole(config('shop-manager.permission.default_shop_roles', ['shop_owner', 'shop_admin']), $shop) ||
                   $user->hasRole(config('shop-manager.permission.platform_shop_admin_roles', ['platform_admin', 'platform_superadmin']));
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Any authenticated user can attempt to create a shop.
     */
    public function create(User $user): bool
    {
        return true; // Further validation (e.g., max shops per user) can be done in controller/service
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Shop $shop): bool
    {
        return $user->id === $shop->owner_id ||
               $user->hasShopRole(config('shop-manager.permission.default_shop_roles', ['shop_owner', 'shop_admin']), $shop) || // Ensure 'shop_owner' is in default_shop_roles for this check
               $user->hasRole(config('shop-manager.permission.platform_shop_admin_roles', ['platform_admin', 'platform_superadmin']));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Shop $shop): bool
    {
        // Typically only owner or platform super admin can delete
        return $user->id === $shop->owner_id ||
               $user->hasRole(config('shop-manager.permission.platform_shop_admin_roles', ['platform_superadmin'])); // More restrictive for delete
    }

    /**
     * Determine whether the user can manage members of the shop.
     */
    public function manageMembers(User $user, Shop $shop): bool
    {
        return $user->id === $shop->owner_id ||
               $user->hasShopRole(config('shop-manager.permission.default_shop_roles', ['shop_owner', 'shop_admin']), $shop) ||
               $user->hasRole(config('shop-manager.permission.platform_shop_admin_roles', ['platform_admin', 'platform_superadmin']));
    }

    /**
     * Determine whether the user can create posts for the shop.
     */
    public function createShopPost(User $user, Shop $shop): bool
    {
        $editableRoles = array_intersect(
            config('shop-manager.permission.default_shop_roles', ['shop_owner', 'shop_admin', 'shop_editor']),
            ['shop_owner', 'shop_admin', 'shop_editor'] // Ensure we only check for roles that make sense for posting
        );
        return $user->id === $shop->owner_id ||
               $user->hasShopRole($editableRoles, $shop) ||
               $user->hasRole(config('shop-manager.permission.platform_shop_admin_roles', ['platform_admin', 'platform_superadmin']));
    }

    /**
     * Determine whether the user can upload media for the shop (logo, cover).
     */
    public function uploadShopMedia(User $user, Shop $shop): bool
    {
         // Similar to update permission or slightly more relaxed if editors can change logo/cover
        return $this->update($user, $shop);
    }


    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, Shop $shop): bool
    // {
    //     return $user->hasRole('platform_admin');
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, Shop $shop): bool
    // {
    //     return $user->hasRole('platform_superadmin');
    // }
}

<?php

namespace Ijideals\ShopManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Ijideals\ShopManager\Models\Shop; // As per your config
use App\Models\User; // As per your config, likely App\Models\User
use Spatie\Permission\Models\Role;


class ShopMemberController extends Controller
{
    protected $shopModelClass;
    protected $userModelClass;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->shopModelClass = config('shop-manager.shop_model', Shop::class);
        $this->userModelClass = config('shop-manager.user_model', User::class);
    }

    /**
     * List members of a specific shop with their roles.
     */
    public function index(string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        // Authorization: Check if user can view members. For simplicity, use 'manageMembers' permission.
        $this->authorize('manageMembers', $shop); // Or a more granular 'viewMembers' if defined in policy

        $members = $shop->getMembersWithShopRoles(); // Using the method defined in Shop model

        return response()->json($members);
    }

    /**
     * Add a user to a shop with a specific role.
     */
    public function addMember(Request $request, string $shopSlugOrId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $this->authorize('manageMembers', $shop);
        $this->authorize('manageMembers', $shop);

        $validated = $request->validate([
            'user_id' => 'required|exists:'.app($this->userModelClass)->getTable().',id',
            'role' => 'required|string', // e.g., 'shop_editor', 'shop_moderator'
        ]);

        $userToAdd = $this->userModelClass::find($validated['user_id']);
        if (!$userToAdd) {
            return response()->json(['message' => 'User to add not found.'], 404); // Should be caught by 'exists' validation
        }

        // Ensure the role exists globally and is a valid shop role (not a platform role for example)
        try {
            $roleInstance = Role::findByName($validated['role'], $userToAdd->guard_name);
            if(!in_array($roleInstance->name, config('shop-manager.permission.default_shop_roles', ['shop_admin', 'shop_editor', 'shop_viewer']))){
                 return response()->json(['message' => "Invalid shop role: {$validated['role']}"], 422);
            }
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            return response()->json(['message' => "Role '{$validated['role']}' does not exist."], 422);
        }

        if ($shop->owner_id === $userToAdd->id && $validated['role'] !== config('shop-manager.owner_default_role', 'shop_admin')) {
            return response()->json(['message' => 'Shop owner must have the primary admin role for the shop.'], 422);
        }


        // Use the method on the Shop model (which uses direct pivot attach for team context)
        if ($shop->addMember($userToAdd, $validated['role'])) {
             // $userToAdd->assignShopRole($validated['role'], $shop); // Alternative if User model's method is preferred & correctly handles team context
            return response()->json(['message' => "User {$userToAdd->name} added to shop {$shop->name} as {$validated['role']}."]);
        }
        return response()->json(['message' => "Failed to add user to shop, or user already has this role."], 400);
    }

    /**
     * Update a member's role in a shop.
     * (Essentially remove old role, add new role for that shop)
     */
    public function updateMemberRole(Request $request, string $shopSlugOrId, int $userId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        // Authorization: Gate::authorize('manageMembers', $shop);
        if (Auth::id() !== $shop->owner_id && !Auth::user()->hasShopRole('shop_admin', $shop) && !Auth::user()->hasRole('platform_admin')) {
             return response()->json(['message' => 'Unauthorized to manage members for this shop.'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|string',
        ]);

        $member = $this->userModelClass::find($userId);
        if (!$member) {
            return response()->json(['message' => 'Member not found.'], 404);
        }

        if ($shop->owner_id === $member->id && $validated['role'] !== config('shop-manager.owner_default_role', 'shop_admin')) {
            return response()->json(['message' => 'Shop owner role cannot be changed from the primary admin role of the shop.'], 422);
        }

        // Ensure the new role is a valid shop role
        try {
            $newRoleInstance = Role::findByName($validated['role'], $member->guard_name);
             if(!in_array($newRoleInstance->name, config('shop-manager.permission.default_shop_roles', ['shop_admin', 'shop_editor', 'shop_viewer']))){
                 return response()->json(['message' => "Invalid shop role: {$validated['role']}"], 422);
            }
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            return response()->json(['message' => "Role '{$validated['role']}' does not exist."], 422);
        }

        // Remove all existing shop-specific roles for this user in this shop
        $shop->removeMemberRole($member); // Removes all roles for this user in this shop

        // Assign the new role
        if ($shop->addMember($member, $validated['role'])) {
            return response()->json(['message' => "User {$member->name}'s role in shop {$shop->name} updated to {$validated['role']}."]);
        }
        return response()->json(['message' => "Failed to update user's role."], 400);

    }

    /**
     * Remove a member from a shop (removes all their roles for that shop).
     */
    public function removeMember(string $shopSlugOrId, int $userId)
    {
        $shop = $this->findShop($shopSlugOrId);
        if (!$shop) {
            return response()->json(['message' => 'Shop not found.'], 404);
        }

        $this->authorize('manageMembers', $shop);

        $member = $this->userModelClass::find($userId);
        if (!$member) {
            return response()->json(['message' => 'Member not found.'], 404);
        }

        if ($shop->owner_id === $member->id) {
            return response()->json(['message' => 'Cannot remove the shop owner.'], 403);
        }

        if ($shop->removeMemberRole($member)) { // This removes all roles for the user in this shop
            return response()->json(['message' => "User {$member->name} removed from shop {$shop->name}."]);
        }
        return response()->json(['message' => 'Failed to remove user from shop, or user was not a member.'], 400);
    }

    protected function findShop(string $slugOrId): ?Shop
    {
        $query = $this->shopModelClass::query();
        if (is_numeric($slugOrId)) {
            return $query->find((int)$slugOrId);
        }
        return $query->where('slug', $slugOrId)->first();
    }
}

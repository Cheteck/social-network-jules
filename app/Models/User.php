<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Ijideals\Commentable\Concerns\CanComment;
use Ijideals\Commentable\Contracts\CommenterContract;
use Ijideals\Followable\Followable;
use Ijideals\Likeable\Concerns\CanLike;
use Ijideals\Likeable\Contracts\Liker as LikerContract;
use Ijideals\MediaUploader\Concerns\HasMedia;
use Ijideals\NotificationSystem\Concerns\HasNotifications;
use Ijideals\SocialPosts\Concerns\HasSocialPosts;
use Ijideals\UserProfile\Concerns\HasProfile;
use Ijideals\UserSettings\Concerns\HasSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles; // Import HasSettings trait

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Ijideals\SocialPosts\Models\Post[] $posts
 * @property-read \Illuminate\Database\Eloquent\Collection|\Ijideals\ShopManager\Models\Shop[] $shopsOwned
 * @property-read \Ijideals\UserProfile\Models\UserProfile|null $profile
 * @property-read string|null $avatar_url
 * @property-read string|null $banner_url
 *
 * @method bool hasShopRole(string|array $role, \Ijideals\ShopManager\Models\Shop $shop)
 * @method \Illuminate\Database\Eloquent\Relations\MorphMany ijidealsSystemNotifications()
 * @method \Illuminate\Database\Eloquent\Relations\MorphMany ijidealsSystemReadNotifications()
 * @method \Illuminate\Database\Eloquent\Relations\MorphMany ijidealsSystemUnreadNotifications()
 *
 * // Rely on `use Notifiable;` and `use HasRoles;` for PHPStan to pick up methods from these traits.
 * // @mixin \Illuminate\Notifications\Notifiable
 * // @mixin \Spatie\Permission\Traits\HasRoles
 * @mixin \Ijideals\Followable\Followable
 * @mixin \Ijideals\SocialPosts\Concerns\HasSocialPosts
 * @mixin \Ijideals\UserProfile\Concerns\HasProfile
 * @mixin \Ijideals\Likeable\Concerns\CanLike
 * @mixin \Ijideals\Commentable\Concerns\CanComment
 * @mixin \Ijideals\MediaUploader\Concerns\HasMedia
 * @mixin \Ijideals\NotificationSystem\Concerns\HasNotifications
 * @mixin \Ijideals\UserSettings\Concerns\HasSettings
 */
class User extends Authenticatable implements CommenterContract, LikerContract // Implement contracts
{
    use CanComment;
    use CanLike;
    use Followable;
    use HasApiTokens;
    use HasFactory;
    use HasMedia;
    // Laravel's default Notifiable trait
    use HasNotifications { // Custom HasNotifications trait with conflict resolution
        Notifiable::notifications insteadof HasNotifications;
        HasNotifications::notifications as ijidealsSystemNotifications;
        Notifiable::readNotifications insteadof HasNotifications;
        HasNotifications::readNotifications as ijidealsSystemReadNotifications;
        Notifiable::unreadNotifications insteadof HasNotifications;
        HasNotifications::unreadNotifications as ijidealsSystemUnreadNotifications;
    }
    use HasProfile;
    use HasRoles;
    use HasSettings;
    use HasSocialPosts;
    use Notifiable;
    use Searchable;

    // If your primary guard for users managing shops is 'api', uncomment and set this:
    // protected $guard_name = 'api';

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \IJIDeals\SocialPosts\Models\Post> $posts
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \Ijideals\ShopManager\Models\Shop> $shopsOwned
     */

    // Example accessors for specific media collections (like avatar)
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = $this->getFirstMedia('avatar'); // 'avatar' is the collection name

        return $avatar ? $avatar->getFullUrl() : null; // Or return a default avatar URL
    }

    public function getBannerUrlAttribute(): ?string
    {
        $banner = $this->getFirstMedia('banner'); // 'banner' is the collection name

        return $banner ? $banner->getFullUrl() : null; // Or return a default banner URL
    }

    // The extra closing comment "*/" was here and has been removed.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Customize array based on what you want to make searchable
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Consider adding profile bio if it's relevant for search
            // 'profile_bio' => $this->profile?->bio,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'users_index'; // Or use scout.database.table if all in one table
    }

    /**
     * Get the shops owned by the user.
     */
    public function shopsOwned(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('shop-manager.shop_model', \Ijideals\ShopManager\Models\Shop::class), 'owner_id');
    }

    /**
     * Get the shops this user is a member of (has a role in).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function memberOfShops()
    {
        $shopModelClass = config('shop-manager.shop_model', \Ijideals\ShopManager\Models\Shop::class);
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $teamKey = config('permission.column_names.team_foreign_key'); // Should be 'shop_id'

        // Subquery to get shop_ids where the user has a role
        $shopIdsWithRolesForUser = \Illuminate\Support\Facades\DB::table($modelHasRolesTable)
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->whereNotNull($teamKey)
            ->distinct()
            ->pluck($teamKey);

        return $shopModelClass::whereIn('id', $shopIdsWithRolesForUser);
    }

    /**
     * Get the user's roles for a specific shop.
     *
     * @return \Illuminate\Support\Collection Collection of Spatie\Permission\Models\Role
     */
    public function getShopRoles(\Ijideals\ShopManager\Models\Shop $shop): \Illuminate\Support\Collection
    {
        // Spatie's HasRoles trait provides roles() relationship.
        // We filter it by the team_id (shop_id).
        $teamKeyField = config('permission.table_names.model_has_roles').'.'.config('permission.column_names.team_foreign_key');

        return $this->roles()->wherePivot($teamKeyField, $shop->id)->get();
    }

    /**
     * Assign a role to the user for a specific shop.
     * This is a helper that leverages Spatie's team functionality.
     *
     * @param  string|\Spatie\Permission\Contracts\Role  $role
     * @return $this
     */
    public function assignShopRole($role, \Ijideals\ShopManager\Models\Shop $shop)
    {
        // Ensure role exists for the guard and context if necessary
        // $this->assignRole($role->setTeamId($shop->id)); // This was for an older Spatie version or custom setup

        // For Spatie v5+, roles are global. Assignment is team-specific.
        // The HasRoles trait's assignRole method handles an array of roles.
        // To assign a role for a specific team, you'd typically set the team ID on the role object
        // IF roles themselves were team-specific. But they are global.
        // The assignment to a team happens on the model_has_roles pivot table.

        // Spatie's assignRole doesn't directly take a team object.
        // You use a new Role model instance if the role is team specific
        // For global roles assigned to a team scope on a user:
        // 1. Ensure the role exists globally: Role::findByName('shop_admin', $this->guardName);
        // 2. The `assignRole` method on the User model (from HasRoles trait)
        //    will correctly populate the team_foreign_key if 'teams' is true
        //    and the key is set in config/permission.php.
        //    We need to ensure the context of the shop_id is passed or set.
        //    The most straightforward way with Spatie v5+ for teams is often to use
        //    givePermissionTo and assignRole with the team_id context if roles are scoped.
        //    However, roles are global. The link is user -> role -> shop_id on pivot.

        // The assignRole method on the user model from Spatie's HasRoles trait
        // automatically handles the team_id if 'teams' => true is set in config/permission.php
        // and the team_foreign_key is correctly configured.
        // We set the team context BEFORE calling assignRole.

        // This is how Spatie docs suggest for teams:
        // $user->assignRole($role); // if $role is a Role object
        // $user->assignRole('writer'); // if 'writer' is a role name
        // When teams are enabled, and you assign a role, if a team_id is set on the model
        // or passed as context, it should be used.
        // Let's assume the controller will set the context or we manage it here.
        // For now, the standard assignRole should work if Spatie's team context is active.

        // A common pattern is to fetch/create the global role, then assign it.
        // The team_id (shop_id) will be automatically filled in model_has_roles by Spatie
        // if the `permission.teams` is true and `team_foreign_key` is set.
        // No special method is usually needed on the user model itself if using default Spatie behavior.
        // The controller logic will be: $user->assignRole($roleName); after perhaps a $user->setShopContext($shop)
        // if we were to implement such a context setter.

        // For clarity and directness, we'll rely on the controller to manage this.
        // This method is a conceptual placeholder.
        // Actual assignment: $user->assignRole($role->name); // in controller, after ensuring team context if needed
        // Or more directly if the Role model instance is passed:
        // $this->roles()->attach($role->id, [config('permission.column_names.team_foreign_key') => $shop->id]);
        // But assignRole should handle it.

        // The key is that spatie/laravel-permission's HasRoles trait's assignRole method
        // will look for a team_id if the teams config is true.
        // We will need to ensure the team_id (shop_id) is available when assignRole is called.
        // This is typically handled by setting a property on the User model that Spatie's trait reads,
        // or by Spatie automatically picking it up from the context if the model itself is a "team" model.
        // Since User is not the team model (Shop is), this needs careful handling in the controller.

        // For now, this method is a high-level representation.
        // The actual assignment will be $user->assignRole($roleName);
        // The Shop context for the role assignment will be handled by Spatie if configured.
        // The role itself is global, its assignment to user is scoped to shop_id.
        return $this; // Placeholder
    }
}

<?php

namespace Ijideals\ShopManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Ijideals\MediaUploader\Concerns\HasMedia;
use Laravel\Scout\Searchable; // Import Scout's Searchable trait

class Shop extends Model
{
    use HasFactory, HasMedia, Searchable; // Use Scout's Searchable trait

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array', // For any shop-specific settings
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('shop-manager.shops_table', 'shops');
    }

    /**
     * The user who owns this shop.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('shop-manager.user_model'), 'owner_id');
    }

    /**
     * Generate a slug when setting the name attribute or if slug is empty.
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug']) || $this->isDirty('name')) {
            $this->attributes['slug'] = $this->generateUniqueSlug($value);
        }
    }

    /**
     * Generate a unique slug for the shop.
     *
     * @param string $name
     * @return string
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Check if slug exists and append counter if it does
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? null)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }
        return $slug;
    }

    /**
     * Get the route key for the model.
     * Allows route model binding using the slug.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Accessor for shop logo URL
    public function getLogoUrlAttribute(): ?string
    {
        $logoCollectionName = config('shop-manager.media_collections.shop_logo.name', 'shop_logo');
        $media = $this->getFirstMedia($logoCollectionName);
        return $media ? $media->getFullUrl() : null; // Add a default logo URL if desired
    }

    // Accessor for shop cover image URL
    public function getCoverImageUrlAttribute(): ?string
    {
        $coverCollectionName = config('shop-manager.media_collections.shop_cover_image.name', 'shop_cover_image');
        $media = $this->getFirstMedia($coverCollectionName);
        return $media ? $media->getFullUrl() : null; // Add a default cover URL if desired
    }

    // Relationship for shop members/staff (if using a pivot table beyond Spatie)
    // public function members()
    // {
    //     // This would be for a simple pivot table, not directly using Spatie's team roles for membership tracking.
    //     return $this->belongsToMany(config('shop-manager.user_model'), config('shop-manager.shop_user_table', 'shop_user'))
    //                 ->withTimestamps();
    // }

    /**
     * Get members of this shop with their roles within this shop.
     * This leverages Spatie's team functionality.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMembersWithShopRoles(): \Illuminate\Support\Collection
    {
        $userModelClass = app(config('shop-manager.user_model'));
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key'); // shop_id

        // Get users who have a role specifically for this shop.
        return $userModelClass::query()
            ->select('users.*', "{$rolesTable}.name as shop_role_name") // Adjust users.* if User table is aliased or different
            ->join($modelHasRolesTable, function ($join) use ($modelHasRolesTable, $userModelClass, $teamKey) {
                $join->on("{$modelHasRolesTable}.model_id", '=', 'users.id')
                     ->where("{$modelHasRolesTable}.model_type", $userModelClass->getMorphClass())
                     ->where("{$modelHasRolesTable}.{$teamKey}", $this->getKey());
            })
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
            ->distinct() // A user might be directly assigned multiple roles for the same shop, though unusual.
            ->get();
    }

    /**
     * Add a user to this shop with a specific role.
     *
     * @param \App\Models\User $user The user model instance.
     * @param string $roleName The name of the role to assign (e.g., 'shop_editor').
     * @return bool
     */
    public function addMember(\App\Models\User $user, string $roleName): bool
    {
        // Ensure the role is valid for the application (global role name)
        $role = app(\Spatie\Permission\Contracts\Role::class)->findByName($roleName, $user->guard_name);
        if (!$role) {
            // throw new \Spatie\Permission\Exceptions\RoleDoesNotExist("Role `{$roleName}` does not exist.");
            return false;
        }
        // The assignRole method on the User model (from Spatie's HasRoles trait)
        // will handle the team_id (shop_id) correctly because we've configured 'teams' => true
        // and 'team_foreign_key' => 'shop_id' in config/permission.php.
        // Spatie's trait checks for a team_id property on the Role model or if the current model context has a team.
        // When assigning a role to a user, if the `Role` model itself had a `team_id` (which it doesn't by default),
        // it would be used. Since it doesn't, Spatie relies on the context or direct pivot manipulation.
        // The most robust way is to ensure the User model's `assignRole` is called correctly.
        // For team-specific assignment, the User model's `assignRole` needs to be aware of the team.
        // Spatie's documentation often shows examples where you set a team context on the user
        // or use methods on a team model if your team model itself uses HasRoles.
        // In our case, Shop is the team. User is the model getting role.

        // This should correctly use the shop_id as the team_id due to config.
        // $user->assignRole([$roleName]); // This assigns a global role if team context isn't set.

        // To assign a role to a user for a specific team (shop),
        // we need to ensure the team_id is set in the model_has_roles table.
        // The assignRole method on the User model should handle this if the
        // Role object is properly configured or if we directly manipulate the pivot.
        // Let's use direct pivot manipulation for clarity with teams here,
        // as `assignRole`'s team behavior can sometimes be subtle depending on Spatie version / setup.

        // Check if user already has this role for this shop to avoid duplicates if not handled by attach.
        if (!$user->hasShopRole($roleName, $this)) {
             $user->roles()->attach($role->id, [config('permission.column_names.team_foreign_key') => $this->id]);
             return true;
        }
        return false; // Already has this role in this shop
    }

    /**
     * Remove a user's role from this shop.
     * If no role is specified, removes all roles of the user for this shop.
     *
     * @param \App\Models\User $user
     * @param string|null $roleName
     * @return bool
     */
    public function removeMemberRole(\App\Models\User $user, ?string $roleName = null): bool
    {
        if ($roleName) {
            $role = app(\Spatie\Permission\Contracts\Role::class)->findByName($roleName, $user->guard_name);
            if ($role) {
                return $user->roles()->detach($role->id, [config('permission.column_names.team_foreign_key') => $this->id]) > 0;
            }
        } else {
            // Remove all roles for this user in this shop
            return $user->roles()->detach(null, [config('permission.column_names.team_foreign_key') => $this->id]) > 0;
        }
        return false;
    }

    /**
     * Check if a user is a member of this shop (has any role).
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function hasMember(\App\Models\User $user): bool
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $teamKey = config('permission.column_names.team_foreign_key');

        return \Illuminate\Support\Facades\DB::table($modelHasRolesTable)
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->where($teamKey, $this->getKey())
            ->exists();
    }


    // If shops can have posts (using ijideals/social-posts polymorphically)
    // public function posts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    // {
    //    // Assuming Post model has a 'authorable' morphTo relationship
    //    return $this->morphMany(\Ijideals\SocialPosts\Models\Post::class, 'authorable');
    // }


    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
       return \Ijideals\ShopManager\Database\Factories\ShopFactory::new();
    }

    /**
     * The posts associated with this shop (shop as author).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function posts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        $postModelClass = config('shop-manager.post_model_class', \Ijideals\SocialPosts\Models\Post::class);
        return $this->morphMany($postModelClass, 'author'); // 'author' matches the morphTo name in Post model
    }

    /**
     * Create a new post authored by this shop.
     *
     * @param array $attributes Attributes for the post (e.g., ['content' => '...'])
     * @return \Illuminate\Database\Eloquent\Model The created Post instance.
     */
    public function createPost(array $attributes): Model
    {
        return $this->posts()->create($attributes);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'owner_name' => $this->owner?->name, // Index owner's name for searching shops by owner
        ];
    }

    /**
     * Get the name of the index associated with the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'shops_index';
    }
}

# Laravel Shop Manager (ijideals/shop-manager)

This package provides functionality to create and manage "shops" or "business pages" within your Laravel application, similar to Facebook Pages. It includes granular role-based access control for shop management using `spatie/laravel-permission` with its "teams" feature (where each shop acts as a team).

## Features

-   Create and manage shops (name, description, logo, cover image, contact info, etc.).
-   Each shop has an owner (a User).
-   Slug generation for SEO-friendly URLs.
-   Integration with `spatie/laravel-permission` for role-based access control:
    -   Uses `shop_id` as the "team" identifier.
    -   Defines default shop-specific roles (e.g., `shop_admin`, `shop_editor`) and platform-level roles.
    -   Allows assigning users to shops with specific roles within that shop.
-   Integration with `ijideals/media-uploader` for shop logo and cover images.
-   Allows shops to be authors of posts (via integration with `ijideals/social-posts` or a similar polymorphic post model).
-   Makes shops searchable via `ijideals/search-engine` (Laravel Scout).
-   API endpoints for managing shops and their members.

## Installation

1.  **Require Dependencies:**
    Ensure you have `spatie/laravel-permission` and `ijideals/media-uploader` (and its dependencies like `intervention/image`) in your main application's `composer.json`.
    ```bash
    composer require spatie/laravel-permission
    composer require ijideals/media-uploader
    # Ensure ijideals/social-posts and ijideals/search-engine are also present if using those integrations
    ```

2.  **Require this package:**
    ```bash
    composer require ijideals/shop-manager
    ```
    (If local, ensure path repository is set in main `composer.json` for all `ijideals/*` packages)

3.  **Service Providers:**
    Laravel's auto-discovery should detect `Ijideals\ShopManager\Providers\ShopManagerServiceProvider` and `Spatie\Permission\PermissionServiceProvider`. If not, add them to `config/app.php`.

4.  **Publish `spatie/laravel-permission` Configuration & Migration:**
    ```bash
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
    ```
    Then, **modify `config/permission.php`**:
    ```php
    // In config/permission.php
    'teams' => true,
    'column_names' => [
        // ... other column names
        'team_foreign_key' => 'shop_id', // Important!
    ],
    'teams_foreign_key_null_when_no_team' => true, // Recommended for global roles
    ```

5.  **Publish ShopManager Configuration & Migrations:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\ShopManager\Providers\ShopManagerServiceProvider" --tag="shop-manager-config"
    php artisan vendor:publish --provider="Ijideals\ShopManager\Providers\ShopManagerServiceProvider" --tag="shop-manager-migrations"
    php artisan vendor:publish --provider="Ijideals\ShopManager\Providers\ShopManagerServiceProvider" --tag="shop-manager-seeders"
    ```
    This publishes:
    *   `config/shop-manager.php`: Configure User/Shop models, table names, default roles, media collections.
    *   The migration for the `shops` table.
    *   `RolesAndPermissionsSeeder.php` to `database/seeders/`.

6.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```
    This will create tables for `spatie/laravel-permission` (with `shop_id` column) and the `shops` table.

7.  **Seed Roles & Permissions:**
    Add `Ijideals\ShopManager\Database\Seeders\RolesAndPermissionsSeeder::class` to your `database/seeders/DatabaseSeeder.php`:
    ```php
    // In DatabaseSeeder.php
    public function run(): void
    {
        // ... other seeders
        $this->call(\Ijideals\ShopManager\Database\Seeders\RolesAndPermissionsSeeder::class);
    }
    ```
    Then run:
    ```bash
    php artisan db:seed
    ```

8.  **Prepare User Model:**
    Your `App\Models\User` (or the model configured in `shop-manager.user_model`) must use the `Spatie\Permission\Traits\HasRoles` trait and the `Ijideals\MediaUploader\Concerns\HasMedia` trait (if users also have media like avatars).
    ```php
    <?php
    namespace App\Models;
    // ...
    use Spatie\Permission\Traits\HasRoles;
    use Ijideals\MediaUploader\Concerns\HasMedia; // If user has own media
    use Ijideals\ShopManager\Concerns\ManagesShops; // Optional: if you add helper traits to User

    class User extends Authenticatable // ...
    {
        use HasRoles, HasMedia; // ... other traits
        // protected $guard_name = 'api'; // If using a different guard for permissions

        public function shopsOwned(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(config('shop-manager.shop_model'), 'owner_id');
        }
        // Add methods like getShopRoleNames, hasShopRole, assignShopRole, removeShopRole as shown in development steps
    }
    ```

9.  **Prepare Shop Model:**
    The `Ijideals\ShopManager\Models\Shop` model already uses `HasMedia`. Ensure it's configured in `config/shop-manager.php`.

## Usage

### Creating a Shop

An authenticated user can create a shop. They will automatically be assigned the primary admin role for that shop (e.g., `shop_admin`).

```php
// Example in a controller
$user = Auth::user();
$shop = $user->shopsOwned()->create([
    'name' => 'My Awesome Shop',
    'description' => 'Selling awesome things.',
    // ... other shop attributes
]);

// The ShopController::store method handles assigning the owner the 'shop_admin' role for this shop.
// $user->assignShopRole('shop_admin', $shop); // or however you implement role assignment for teams
```

### Managing Shop Members & Roles

Use the API endpoints or methods on the `Shop` and `User` models.

**Shop Model Methods:**
*   `$shop->getMembersWithShopRoles()`: Get a collection of users with their `shop_role_name`.
*   `$shop->addMember(User $user, string $roleName)`: Adds a user to the shop with a role.
*   `$shop->removeMemberRole(User $user, ?string $roleName = null)`: Removes a specific role or all roles for a user in the shop.
*   `$shop->hasMember(User $user)`: Checks if a user is a member.

**User Model Methods (examples shown previously, ensure they are implemented):**
*   `$user->memberOfShops()`: Gets shops the user is a member of.
*   `$user->getShopRoleNames(Shop $shop)`: Gets user's role names for a specific shop.
*   `$user->hasShopRole($role, Shop $shop)`: Checks for a specific role in a shop.
*   `$user->assignShopRole($role, Shop $shop)`: Assigns a role to the user for the shop.
*   `$user->removeShopRole($role, Shop $shop)`: Removes a role for the user in the shop.

### Permissions

Permissions are defined in `RolesAndPermissionsSeeder.php`. You can check permissions using Spatie's standard methods, keeping in mind the team/shop context.
Laravel's `Gate` will also work with Spatie permissions. For team permissions, you might need to pass the Shop instance:
`Gate::forUser($user)->authorize('edit_shop_settings', $shop);`

### Shop Media (Logo, Cover)

Use the `HasMedia` trait methods on a `Shop` instance:
```php
$shop = Shop::find(1);
// Assuming 'shop_logo' and 'shop_cover_image' are configured collections
$shop->addMedia($request->file('logo_file'), 'shop_logo');
$logoUrl = $shop->logo_url; // Accessor
```
The `ShopController` already handles logo and cover image uploads during shop creation/update.

### Shop Posts

If integrated with `ijideals/social-posts` (where `Post.author` is polymorphic):
```php
$shop = Shop::find(1);
$shopPost = $shop->createPost(['content' => 'A new announcement from our shop!']);
// $shopPost->author will be the $shop instance.
```

### API Endpoints

Prefix: `config('shop-manager.route_prefix')` (default: `api/v1/shops`)

**Shops:**
*   `GET /`: List active shops.
*   `POST /`: Create a new shop (Auth required).
*   `GET /{shopSlugOrId}`: Show a specific shop.
*   `PUT|PATCH /{shopSlugOrId}`: Update a shop (Auth & Permission required).
*   `DELETE /{shopSlugOrId}`: Delete a shop (Auth & Permission required).

**Shop Members (under `/shops/{shopSlugOrId}/members`):** (All Auth required)
*   `GET /`: List shop members and their roles.
*   `POST /`: Add a member with a role.
*   `PUT|PATCH /{userId}/role`: Update a member's role.
*   `DELETE /{userId}`: Remove a member from the shop.

## Testing

```bash
./vendor/bin/phpunit packages/ijideals/shop-manager/tests
```

## Localization

This package's API messages can be translated. Publish language files with:
```bash
php artisan vendor:publish --provider="Ijideals\ShopManager\Providers\ShopManagerServiceProvider" --tag="shop-manager-lang"
```
(This tag will need to be added to the ServiceProvider if you add translations).

---

This README provides a comprehensive overview. Remember to implement any missing helper methods on User/Shop models as discussed during development for cleaner interactions.
```

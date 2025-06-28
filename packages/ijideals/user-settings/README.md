# Laravel User Settings (ijideals/user-settings)

This package provides a simple way for users to manage their personal settings within a Laravel application. Settings are stored as key-value pairs and can be easily retrieved and updated. Default settings and type casting are supported via configuration.

## Features

-   Store user-specific settings in the database.
-   Define default settings and their values in a configuration file.
-   Settings keys are namespaced (e.g., `notifications.new_like.database`).
-   Retrieve individual settings with fallback to default values.
-   Retrieve all user settings merged with defaults.
-   Update one or multiple settings.
-   Type casting for retrieved setting values (e.g., boolean, integer).
-   `HasSettings` trait for easy integration into the `User` model.
-   API endpoints for users to manage their settings.
-   Integration example with a notification system to respect user preferences.

## Installation

1.  **Require the package:**
    ```bash
    composer require ijideals/user-settings
    ```
    (If local, ensure path repository is set in main `composer.json`)

2.  **Service Provider:**
    Laravel's auto-discovery should detect `Ijideals\UserSettings\Providers\UserSettingsServiceProvider`. If not, add it to `config/app.php`.

3.  **Publish Configuration & Migrations:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\UserSettings\Providers\UserSettingsServiceProvider" --tag="user-settings-config"
    php artisan vendor:publish --provider="Ijideals\UserSettings\Providers\UserSettingsServiceProvider" --tag="user-settings-migrations"
    ```
    This publishes:
    *   `config/user-settings.php`: Define your application's user settings, their defaults, and casts.
    *   The migration for the `user_settings` table.

4.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```
    This will create the `user_settings` table.

## Configuration (`config/user-settings.php`)

The core of this package is its configuration file.

*   **`usersetting_model`**: The Eloquent model for settings (defaults to package's model).
*   **`table_name`**: Database table name.
*   **`route_prefix`**: API route prefix.
*   **`defaults`**: An associative array defining all available settings and their default values. This acts as a whitelist of allowed setting keys.
    ```php
    'defaults' => [
        'notifications' => [
            'new_like' => ['database' => true, 'email' => false],
            'new_comment' => ['database' => true],
        ],
        'privacy' => [
            'profile_visibility' => 'public',
        ],
    ],
    ```
*   **`casts`**: Define how specific setting values should be cast when retrieved (e.g., `boolean`, `integer`).
    ```php
    'casts' => [
        'notifications.new_like.database' => 'boolean',
        'notifications.new_like.email' => 'boolean',
    ],
    ```

## Usage

### 1. Prepare Your User Model

Add the `Ijideals\UserSettings\Concerns\HasSettings` trait to your `User` model:

```php
<?php
namespace App\Models; // Or your User model namespace

use Illuminate\Foundation\Auth\User as Authenticatable;
use Ijideals\UserSettings\Concerns\HasSettings;
// ...

class User extends Authenticatable // ...
{
    use HasSettings;
    // ...
}
```

### 2. Managing Settings

The `HasSettings` trait provides the following methods on your User model:

*   `$user->getSetting(string $key, $default = null)`: Get a setting's value. Falls back to config default, then to the provided default. Applies casting.
    ```php
    $wantsEmailForLikes = $user->getSetting('notifications.new_like.email', false);
    ```
*   `$user->setSetting(string $key, $value)`: Set/update a single setting.
    ```php
    $user->setSetting('privacy.profile_visibility', 'followers_only');
    ```
*   `$user->setSettings(array $settingsArray)`: Set/update multiple settings.
    ```php
    $user->setSettings([
        'notifications.new_comment.database' => false,
        'privacy.profile_visibility' => 'private',
    ]);
    ```
*   `$user->getAllSettings()`: Get all settings for the user, merged with defaults and properly cast.
*   `$user->getDefaultSettings()`: Get all default settings from the config.
*   `$user->settings()`: The `HasMany` Eloquent relationship to the raw `UserSetting` model instances.

### 3. API Endpoints

Prefix: `config('user-settings.route_prefix')` (default: `api/v1/user/settings`)
All routes require authentication.

*   **`GET /`**: Retrieve user settings.
    *   Returns all settings merged with defaults.
    *   Optional query parameter: `?keys=key1,key2.nestedkey` to fetch only specific settings.
    *   Example: `GET /api/v1/user/settings?keys=notifications.new_like.database,privacy.profile_visibility`
*   **`PUT /`**: Update user settings.
    *   Request body should be a JSON object of key-value pairs:
        ```json
        {
            "notifications.new_like.database": false,
            "privacy.profile_visibility": "private"
        }
        ```
    *   Only keys defined in `config/user-settings.php` under `defaults` will be processed.

### 4. Integration with Other Systems (Example: Notification Preferences)

In another service (e.g., `NotificationCreationService` from `ijideals/notification-system`), you can check a user's preference before proceeding:

```php
// In NotificationCreationService.php (or a Listener)
// ...
$user = $userModelClass::find($userId);

if ($user && method_exists($user, 'getSetting')) {
    $settingKey = "notifications.{$type}.database"; // e.g., notifications.new_like.database
    $isNotificationEnabled = $user->getSetting($settingKey, true); // Default to true if setting missing

    if (!$isNotificationEnabled) {
        // Log and skip creating DB notification
        return null;
    }
}
// ... proceed to create notification
```

## Testing

```bash
# From your Laravel application root
./vendor/bin/phpunit packages/ijideals/user-settings/tests
```

## Future Considerations
-   More complex validation rules per setting key.
-   Storing setting definitions (key, type, validation rules, description) in the database for dynamic management.
-   UI for managing settings (outside the scope of this API-focused package).
```

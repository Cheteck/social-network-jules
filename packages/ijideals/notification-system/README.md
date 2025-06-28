# Laravel Notification System (ijideals/notification-system)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ijideals/notification-system.svg?style=flat-square)](https://packagist.org/packages/ijideals/notification-system)
[![Total Downloads](https://img.shields.io/packagist/dt/ijideals/notification-system.svg?style=flat-square)](https://packagist.org/packages/ijideals/notification-system)

This package provides a database-backed notification system for Laravel applications, allowing you to easily notify users about various events within your application.

## Features

-   Stores notifications in the database.
-   Tracks read/unread status for each notification.
-   Attaches to events fired by other packages (e.g., new likes, comments, followers) in a decoupled manner.
-   Provides a `HasNotifications` trait for your `User` model for easy access to notifications.
-   API endpoints to fetch notifications, mark them as read, and get unread counts.
-   Configurable notification types and event-to-listener mapping.
-   Integration with `ijideals/user-settings` package: respects user preferences for receiving database notifications (e.g., a user can disable DB notifications for new likes).

## Installation

1.  **Require the package:**

    ```bash
    composer require ijideals/notification-system
    ```
    If this is a local package, ensure your main `composer.json` has the path repository set up.

2.  **Service Provider:**

    If you are not using Laravel's auto-discovery, add the service provider to your `config/app.php` file:

    ```php
    'providers' => [
        // ...
        Ijideals\NotificationSystem\Providers\NotificationSystemServiceProvider::class,
    ],
    ```

## Configuration

1.  **Publish the configuration file:**

    ```bash
    php artisan vendor:publish --provider="Ijideals\NotificationSystem\Providers\NotificationSystemServiceProvider" --tag="notification-system-config"
    ```
    This will create a `config/notification-system.php` file. Review and customize:
    *   `user_model`: Your application's User model.
    *   `notification_model`: The model used for notifications (defaults to package's model).
    *   `table_name`: Database table name for notifications.
    *   `event_listeners`: **Crucial part.** Map events from other packages to the notification listeners provided by this package (or your custom listeners).
    *   `notification_types`: Define your application's notification types.

2.  **Publish the migration file:**

    ```bash
    php artisan vendor:publish --provider="Ijideals\NotificationSystem\Providers\NotificationSystemServiceProvider" --tag="notification-system-migrations"
    ```

3.  **Run the migrations:**

    ```bash
    php artisan migrate
    ```
    This will create the `notifications` table (or the name you configured).

## Usage

### 1. Prepare Your User Model

Add the `Ijideals\NotificationSystem\Concerns\HasNotifications` trait to your `User` model:

```php
<?php

namespace App\Models; // Or your User model namespace

use Illuminate\Foundation\Auth\User as Authenticatable;
use Ijideals\NotificationSystem\Concerns\HasNotifications;
// ... other necessary imports

class User extends Authenticatable // ... potentially implements other contracts
{
    use HasNotifications;
    // ... other traits like Notifiable (Laravel's default), HasFactory etc.

    // ... your model properties and methods
}
```

### 2. Configure Event Listeners

In `config/notification-system.php`, define which events should trigger notification creation. This package provides some basic listeners you can use or extend:

-   `Ijideals\NotificationSystem\Listeners\SendNewLikeNotificationListener`
-   `Ijideals\NotificationSystem\Listeners\SendNewCommentNotificationListener`
-   `Ijideals\NotificationSystem\Listeners\SendNewFollowerNotificationListener`

**Example `event_listeners` configuration:**

```php
// config/notification-system.php
'event_listeners' => [
    // Assuming these events are fired by your other packages
    \Ijideals\Likeable\Events\ModelLiked::class => [
        \Ijideals\NotificationSystem\Listeners\SendNewLikeNotificationListener::class,
    ],
    \Ijideals\Commentable\Events\CommentPosted::class => [
        \Ijideals\NotificationSystem\Listeners\SendNewCommentNotificationListener::class,
    ],
    \Ijideals\Followable\Events\UserFollowed::class => [ // Make sure this event exists and is fired
        \Ijideals\NotificationSystem\Listeners\SendNewFollowerNotificationListener::class,
    ],
],
```
**Important:** Ensure that the packages mentioned above (`likeable`, `commentable`, `followable`) actually **fire** these events. You may need to modify them to do so if they don't already. The listeners expect specific data structures from these events (e.g., `$event->like`, `$event->comment`, `$event->follower`, `$event->followed`).

### 3. Triggering Notifications

Notifications are triggered automatically when the configured events are dispatched. The `NotificationCreationService` (called by event listeners) will:
1.  Check the recipient user's settings (if `ijideals/user-settings` package is integrated and the User model uses `HasSettings` trait).
2.  For a notification of type `foo`, it checks a setting like `notifications.foo.database`.
3.  If the setting is `false`, the database notification is not created for that user for that event. Otherwise, or if the setting is not found (defaults to `true`), the notification is created.

This allows users to customize which types of notifications they receive in their on-site notification list.

### 4. Accessing Notifications (User Model)

The `HasNotifications` trait adds helpful methods to your User model:

```php
$user = Auth::user();

// Get all notifications (paginated by default via API, or all via relation)
$notifications = $user->notifications()->get(); // Eloquent collection
$unreadNotifications = $user->unreadNotifications()->get();
$readNotifications = $user->readNotifications()->get();

// Get unread count (efficiently)
$count = $user->unread_notifications_count; // Accessor
// or $count = $user->unreadNotifications()->count();

// Mark all as read
$user->markAllNotificationsAsRead();

// Clear notifications
$user->clearNotifications(); // Clears all
$user->clearNotifications(true); // Clears only read notifications
```

### 5. API Endpoints

The package provides the following API endpoints (prefix configurable via `notification-system.route_prefix`, default: `api/v1/notifications`):

*   **`GET /`**: List notifications for the authenticated user.
    *   Query parameter: `?status=read` or `?status=unread` to filter.
    *   (Name: `notifications.index`)
*   **`GET /unread-count`**: Get the count of unread notifications.
    *   (Name: `notifications.unread.count`)
*   **`PATCH /{notificationId}/read`**: Mark a specific notification as read.
    *   `notificationId` should be the UUID of the notification.
    *   (Name: `notifications.markAsRead`)
*   **`POST /mark-all-as-read`**: Mark all unread notifications as read.
    *   (Name: `notifications.markAllAsRead`)
*   **`DELETE /{notificationId}`**: Delete a specific notification.
    *   (Name: `notifications.destroy`)
*   **`DELETE /clear-all`**: Delete all notifications for the user.
    *   Query parameter: `?only_read=true` to delete only read ones.
    *   (Name: `notifications.clearAll`)

All routes require authentication.

### Notification Data Structure

Each notification in the database (and returned by the API) has a `type` (string) and a `data` (JSON) field. The `data` field stores contextual information. Examples:

-   For a `new_like` notification:
    ```json
    {
        "liker_id": 123,
        "liker_name": "John Doe",
        "likeable_id": 45,
        "likeable_type": "post", // or your morph alias
        "likeable_summary": "Check out my new photo!"
    }
    ```
-   For a `new_comment` notification:
    ```json
    {
        "commenter_id": 124,
        "commenter_name": "Jane Smith",
        "comment_id": 789,
        "comment_excerpt": "Great post!",
        "commentable_id": 45,
        "commentable_type": "post",
        "commentable_summary": "Check out my new photo!"
    }
    ```

Your frontend will use this `type` and `data` to render the notification appropriately.

### Localization (L10n)

API response messages from this package are translatable.

*   **Publishing Language Files:**
    To publish the language files to your application's `lang/vendor/notification-system` directory, run:
    ```bash
    php artisan vendor:publish --provider="Ijideals\NotificationSystem\Providers\NotificationSystemServiceProvider" --tag="notification-system-lang"
    ```
    You can then customize the translations in `lang/vendor/notification-system/{locale}/notification-system.php`.

*   **Supported Locales:**
    The package includes translations for `en` and `fr` by default. Ensure your application is configured to handle locale switching.

## Testing

```bash
# From your Laravel application root
./vendor/bin/phpunit packages/ijideals/notification-system/tests
```

## Future Considerations

-   Real-time notifications (WebSockets via Laravel Echo).
-   Email or other notification channels (leveraging Laravel's core notification system).
-   Admin interface for viewing/managing notifications.
-   More sophisticated notification grouping/throttling.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) (to be created) for details.

## Security

If you discover any security related issues, please email jules@ai.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) (to be created) for more information.
```

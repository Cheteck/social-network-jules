# Laravel Likeable (ijideals/likeable)

This package provides functionality for Eloquent models to be liked and unliked by users.

## Installation

1.  **Require the package:**
    ```bash
    composer require ijideals/likeable
    ```
    (If local, ensure path repository is set in main `composer.json`)

2.  **Service Provider:**
    Laravel's auto-discovery should detect the `Ijideals\Likeable\Providers\LikeableServiceProvider`. If not, add it to `config/app.php`.

3.  **Publish & Run Migrations:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\Likeable\Providers\LikeableServiceProvider" --tag="likeable-migrations"
    php artisan migrate
    ```

4.  **Publish Configuration (Optional):**
    ```bash
    php artisan vendor:publish --provider="Ijideals\Likeable\Providers\LikeableServiceProvider" --tag="likeable-config"
    ```
    This publishes `config/likeable.php` where you can configure the User model, Like model, table names, and event classes.

## Usage

### 1. Prepare User Model

Add the `Ijideals\Likeable\Concerns\CanLike` trait and implement the `Ijideals\Likeable\Contracts\Liker` contract:

```php
<?php

namespace App\Models; // Or your User model namespace

use Illuminate\Foundation\Auth\User as Authenticatable;
use Ijideals\Likeable\Concerns\CanLike;
use Ijideals\Likeable\Contracts\Liker;

class User extends Authenticatable implements Liker
{
    use CanLike;
    // ...
}
```

### 2. Prepare Likeable Models

Add the `Ijideals\Likeable\Concerns\CanBeLiked` trait and implement the `Ijideals\Likeable\Contracts\Likeable` contract to any model you want to make likeable (e.g., `Post`, `Comment`):

```php
<?php

namespace App\Models; // Or your likeable model's namespace

use Illuminate\Database\Eloquent\Model;
use Ijideals\Likeable\Concerns\CanBeLiked;
use Ijideals\Likeable\Contracts\Likeable;

class Post extends Model implements Likeable
{
    use CanBeLiked;
    // ...
}
```

### 3. Liking and Unliking

```php
$user = User::find(1);
$post = Post::find(1);

// User likes a post
$user->like($post);

// User unlikes a post
$user->unlike($post);

// Toggle like status
$user->toggleLike($post);

// Check if liked
if ($user->hasLiked($post)) {
    // ...
}

// Get likes count for a model
$likesCount = $post->likes_count; // Accessor
// or $likesCount = $post->likes()->count();

// Check if a model instance is liked by a user
if ($post->isLikedBy($user)) {
    // ...
}
```

### 4. Events

The package fires the following event by default (configurable in `config/likeable.php`):
*   `Ijideals\Likeable\Events\ModelLiked` when a model is liked. This event has a public property `$like` (the Like model instance).

### 5. API Routes

The package provides these API routes (prefix `api/v1/likeable` by default):
*   `POST /{likeable_type}/{likeable_id}/like`: Like an item.
*   `DELETE /{likeable_type}/{likeable_id}/unlike`: Unlike an item.
*   `POST /{likeable_type}/{likeable_id}/toggle-like`: Toggle like status.

(Requires authentication)

### 6. Localization (L10n)

API response messages from this package are translatable.

*   **Publishing Language Files:**
    To publish the language files to your application's `lang/vendor/likeable` directory, run:
    ```bash
    php artisan vendor:publish --provider="Ijideals\Likeable\Providers\LikeableServiceProvider" --tag="likeable-lang"
    ```
    You can then customize the translations in `lang/vendor/likeable/{locale}/likeable.php`.

*   **Supported Locales:**
    The package includes translations for `en` and `fr` by default. Ensure your application is configured to handle locale switching (e.g., via a middleware like `SetLocale`).

## Testing

```bash
# From your Laravel application root, if tests are set up in main phpunit.xml
# or navigate to the package directory
./vendor/bin/phpunit packages/ijideals/likeable/tests
```

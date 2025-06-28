# Laravel Commentable (ijideals/commentable)

This package allows Eloquent models to have comments, supporting nested replies and user ownership.

## Installation

1.  **Require the package:**
    ```bash
    composer require ijideals/commentable
    ```
    (If local, ensure path repository is set in main `composer.json`)

2.  **Service Provider:**
    Laravel's auto-discovery should detect `Ijideals\Commentable\Providers\CommentableServiceProvider`. If not, add it to `config/app.php`. (Nous avons créé ce SP pendant l'étape L10n).

3.  **Publish & Run Migrations:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\Commentable\Providers\CommentableServiceProvider" --tag="commentable-migrations"
    php artisan migrate
    ```

4.  **Publish Configuration (Optional):**
    ```bash
    php artisan vendor:publish --provider="Ijideals\Commentable\Providers\CommentableServiceProvider" --tag="commentable-config"
    ```
    This publishes `config/commentable.php` for configuring User model, Comment model, table name, soft deletes, nesting, etc.

## Usage

### 1. Prepare User Model (Commenter)

Add the `Ijideals\Commentable\Concerns\CanComment` trait and implement the `Ijideals\Commentable\Contracts\CommenterContract`:

```php
<?php

namespace App\Models; // Or your User model namespace

use Illuminate\Foundation\Auth\User as Authenticatable;
use Ijideals\Commentable\Concerns\CanComment;
use Ijideals\Commentable\Contracts\CommenterContract;

class User extends Authenticatable implements CommenterContract
{
    use CanComment;
    // ...
}
```

### 2. Prepare Commentable Models

Add the `Ijideals\Commentable\Concerns\CanBeCommentedOn` trait and implement the `Ijideals\Commentable\Contracts\CommentableContract` to any model you want to make commentable (e.g., `Post`):

```php
<?php

namespace App\Models; // Or your commentable model's namespace

use Illuminate\Database\Eloquent\Model;
use Ijideals\Commentable\Concerns\CanBeCommentedOn;
use Ijideals\Commentable\Contracts\CommentableContract;

class Post extends Model implements CommentableContract
{
    use CanBeCommentedOn;
    // ...
}
```

### 3. Working with Comments

```php
$user = User::find(1);
$post = Post::find(1);

// User posts a new comment on a post
$comment = $user->comment($post, 'This is a great post!');

// User replies to an existing comment
$parentComment = Comment::find(1);
$reply = $user->comment($post, 'I agree with this comment!', $parentComment);

// Model adds a comment directly (e.g., by a specific user)
$newComment = $post->addComment('Another insightful comment.', $user);
$replyToNewComment = $post->addComment('Replying to the insightful comment.', $user, $newComment);


// Get comments for a model (top-level only by default if nesting is on)
$topLevelComments = $post->comments()->get();

// Get all comments including replies
$allComments = $post->allComments()->get();

// Update a comment (user must own it)
if ($user->updateComment($comment, 'Updated content for my comment.')) {
    // success
}

// Delete a comment (user must own it)
if ($user->deleteComment($comment)) {
    // success
}
```

### 4. Events

The package fires the following event by default (configurable in `config/commentable.php`):
*   `Ijideals\Commentable\Events\CommentPosted` when a new comment is saved. This event has a public property `$comment` (the Comment model instance, with `commenter`, `commentable`, and `parent.commenter` relations eager loaded).

### 5. API Routes

The package provides these API routes (prefix `api/v1/comments` by default, configurable):
*   `GET /{commentable_type}/{commentable_id}`: List comments for a model.
*   `POST /{commentable_type}/{commentable_id}`: Post a new comment. (Auth required)
*   `PUT|PATCH /{comment_id}`: Update an existing comment. (Auth required, user must own comment)
*   `DELETE /{comment_id}`: Delete a comment. (Auth required, user must own comment)

### 6. Localization (L10n)

API response messages from this package are translatable.

*   **Publishing Language Files:**
    To publish the language files to your application's `lang/vendor/commentable` directory, run:
    ```bash
    php artisan vendor:publish --provider="Ijideals\Commentable\Providers\CommentableServiceProvider" --tag="commentable-lang"
    ```
    You can then customize the translations in `lang/vendor/commentable/{locale}/commentable.php`.

*   **Supported Locales:**
    The package includes translations for `en` and `fr` by default.

## Testing

```bash
# From your Laravel application root
./vendor/bin/phpunit packages/ijideals/commentable/tests
```

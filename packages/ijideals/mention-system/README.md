# IJIDeals Mention System

A Laravel package to handle `@username` mentions in text content and associate them with user records.

## Features

-   Automatically parses text content for `@username` patterns.
-   Creates `Mention` records linking the mentioned user, the mentioner (optional), and the content where the mention occurred.
-   Uses a `HasMentions` trait for easy integration into any Eloquent model.
-   Dispatches a `UserMentioned` event when a new mention is created, allowing integration with notification systems or other services.
-   Handles creation, updating, and deletion of mentions synchronized with the parent model's lifecycle.
-   Prevents self-mentions (user mentioning themselves in their own content).

## Installation

1.  **Require the package via Composer:**
    ```bash
    composer require ijideals/mention-system
    ```
    *(Note: If using a local path repository, ensure your main `composer.json` is configured correctly to find this package.)*

2.  **Run Migrations:**
    The package's service provider automatically loads its migrations. Run Laravel's migration command:
    ```bash
    php artisan migrate
    ```

## Configuration (Optional)

This package currently requires no specific configuration file to be published. The main User model (`App\Models\User`) is assumed by default. If your User model is located elsewhere, you may need to adjust the import statements within the package's models or traits, or consider making this configurable in the future.

## Usage

### 1. Prepare Your User Model

Ensure your `User` model (typically `App\Models\User`) has a `username` field that will be used for matching mentions (e.g., if content says `@john_doe`, it looks for a user with `username` 'john_doe').

### 2. Use the `HasMentions` Trait in Your Content Models

Add the `IJIDeals\MentionSystem\Traits\HasMentions` trait to any Eloquent model that contains text where users can be mentioned (e.g., `Post`, `Comment`).

```php
<?php

namespace App\Models; // Or your model's namespace

use Illuminate\Database\Eloquent\Model;
use IJIDeals\MentionSystem\Traits\HasMentions;

class Post extends Model // Example
{
    use HasMentions;

    protected $fillable = ['body', 'user_id']; // Ensure 'body' and 'user_id' are fillable if used

    /**
     * Get the name of the field on this model that contains the text with potential mentions.
     * This method MUST be implemented by the model using this trait.
     */
    public function getFieldContainingMentions(): string
    {
        return 'body'; // Or 'content', 'message', etc., depending on your model's attribute
    }

    /**
     * Optional: Get the ID of the author of this content.
     * Used to set the 'mentioner_id' in the Mention record.
     * If not implemented, 'mentioner_id' will be null.
     */
    public function getMentionerId(): ?int
    {
        return $this->user_id ?? null; // Assuming your model has a 'user_id' for the author
    }

    // Optional: Define relationship to author if needed for getMentionerId()
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

```

### 3. How It Works

-   When a model using `HasMentions` is **created** or **updated** (and the field specified by `getFieldContainingMentions()` is changed), the trait will automatically:
    1.  Parse the content of that field for `@username` patterns.
    2.  Look up users matching these usernames.
    3.  Create `IJIDeals\MentionSystem\Models\Mention` records for each valid, non-self mention.
    4.  Dispatch an `IJIDeals\MentionSystem\Events\UserMentioned` event for each new `Mention` created.
-   When a model using `HasMentions` is **deleted**, any associated `Mention` records are also deleted.

### 4. Accessing Mentions

You can access mentions related to a model instance:

```php
$post = Post::find(1);
$mentionsInPost = $post->mentions; // Returns a Collection of Mention objects

foreach ($mentionsInPost as $mention) {
    $mentionedUser = $mention->user; // The User who was mentioned
    $mentioner = $mention->mentioner; // The User who made the mention (if available)
}
```

### 5. Listening to Events

The `IJIDeals\MentionSystem\Events\UserMentioned` event is dispatched when a new mention is recorded. You can create listeners for this event, for example, to send notifications.

The event object has a public property `$mention` which is an instance of `IJIDeals\MentionSystem\Models\Mention`.

**Example Listener (e.g., in your `EventServiceProvider`):**

```php
use IJIDeals\MentionSystem\Events\UserMentioned;
use App\Listeners\SendMentionNotification; // Your custom listener

protected $listen = [
    UserMentioned::class => [
        SendMentionNotification::class,
    ],
];
```

## Testing

The package includes unit and feature tests. You can run them using PHPUnit from the package directory or the main application root if configured.

## Future Considerations / TODO

-   Make the User model class and its `username` attribute configurable.
-   Add API endpoints for querying mentions if needed.
-   More sophisticated username parsing (e.g., handling different character sets if usernames are not just ASCII).

## Contributing

Please see CONTRIBUTING.md for details (if such a file exists).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information (if such a file exists).

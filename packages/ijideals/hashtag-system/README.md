# Laravel Hashtag System

A package to add hashtag functionality to Eloquent models in a Laravel application.

## Features

- Define hashtags and associate them with any model.
- Retrieve models by a specific hashtag.
- Trait for easy integration into models.

## Installation

1.  `composer require ijideals/hashtag-system` (Once published or linked locally)
2.  The service provider will be automatically registered.
3.  Run migrations: `php artisan migrate`

## Usage

1.  Use the `Ijideals\HashtagSystem\Traits\HasHashtags` trait in your Eloquent model.
    ```php
    use Ijideals\HashtagSystem\Traits\HasHashtags;

    class Post extends Model
    {
        use HasHashtags;
        // ...
    }
    ```
2.  Managing hashtags:
    ```php
    $post = Post::find(1);

    // Add hashtags (string or array)
    $post->addHashtags('#laravel #php');
    $post->addHashtags(['#eloquent', '#package']);

    // Sync hashtags (removes others)
    $post->syncHashtags('#new #tags');

    // Remove specific hashtags
    $post->removeHashtags('#php');

    // Remove all hashtags
    $post->removeAllHashtags();

    // Get all hashtags for the model
    $hashtags = $post->hashtags;
    ```
3.  Retrieving posts by hashtag:
    ```php
    use Ijideals\HashtagSystem\Models\Hashtag;

    $hashtag = Hashtag::where('slug', 'laravel')->first();
    if ($hashtag) {
        $posts = $hashtag->posts()->get(); // Assuming you have a 'posts' relationship defined in Hashtag model
    }
    ```
## API Endpoints

The package provides the following API endpoints (prefix may vary based on global API prefixing in your application, default is no extra prefix beyond what's defined in the package's `routes/api.php`):

-   `GET /api/hashtags`: List all hashtags (paginated).
-   `GET /api/hashtags/{slug}`: Get a single hashtag by its slug.
-   `GET /api/hashtags/{slug}/posts`: Get posts associated with a specific hashtag (paginated).
-   `GET /api/hashtags/{slug}/items/{type}`: Get items of a specific type (e.g., 'post', 'product') associated with a hashtag (paginated). The `{type}` should correspond to a model name that uses the `HasHashtags` trait and for which a relationship is resolvable on the `Hashtag` model.

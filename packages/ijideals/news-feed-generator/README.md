# Laravel News Feed Generator (ijideals/news-feed-generator)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ijideals/news-feed-generator.svg?style=flat-square)](https://packagist.org/packages/ijideals/news-feed-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/ijideals/news-feed-generator.svg?style=flat-square)](https://packagist.org/packages/ijideals/news-feed-generator)

This package provides a foundation for generating personalized and engaging news feeds for users in a Laravel application. It combines posts from followed users with discovery content, ranking them by relevance.

## Features (Current - Phase 2)

-   Generates a news feed combining posts from users followed and simple discovery content.
-   **Ranking Engine:** Sorts posts by a calculated score based on:
    -   Recency (newer posts get higher scores, with exponential decay).
    -   Engagement (likes and comments, with configurable weights).
-   **Discovery:** Option to include popular posts from non-followed users.
-   Built-in caching layer (`FeedCacheManager`) to improve performance.
-   Paginated API endpoint for retrieving the feed.
-   Configurable cache TTL, pagination size, model dependencies, ranking weights, and discovery settings.
-   Dependencies on `ijideals/followable`, `ijideals/social-posts`, `ijideals/likeable`, and `ijideals/commentable`.

## Future Features

-   More sophisticated ranking factors (e.g., user affinity, content type preference).
-   Advanced content discovery algorithms.
-   Granular cache invalidation strategies (e.g., on new relevant posts).
-   User-specific feed customization options.

## Installation

1.  **Require the package (and its dependencies if not already present):**

    Make sure you have already installed `ijideals/followable`, `ijideals/social-posts`, `ijideals/likeable`, and `ijideals/commentable` as this package relies on them.

    ```bash
    composer require ijideals/news-feed-generator
    ```
    If these are local packages, ensure your main `composer.json` has the path repositories set up for all `ijideals/*` packages.

2.  **Service Provider:**

    If you are not using Laravel's auto-discovery, add the service provider to your `config/app.php` file:

    ```php
    'providers' => [
        // ...
        Ijideals\NewsFeedGenerator\Providers\NewsFeedGeneratorServiceProvider::class,
    ],
    ```

## Configuration

1.  **Publish the configuration file:**

    ```bash
    php artisan vendor:publish --provider="Ijideals\NewsFeedGenerator\Providers\NewsFeedGeneratorServiceProvider" --tag="news-feed-generator-config"
    ```
    This will create a `config/news-feed-generator.php` file. Review and customize the options:
    *   `user_model`: Your application's User model class.
    *   `post_model`: Your application's Post model class.
    *   `cache`: Settings for cache store, prefix, and TTL.
    *   `pagination_items`: Default number of items per page.
    *   `ranking`: Configuration for scoring weights (recency, likes, comments) and recency half-life.
    *   `discovery`: Settings to enable/disable discovery and control the ratio of discovery items.

2.  **Ensure Dependencies are Configured:**
    This package relies on data from `ijideals/followable` (for followed users), `ijideals/social-posts` (for post content), and potentially `ijideals/likeable` and `ijideals/commentable` (for engagement counts used in ranking). Ensure these are installed and your models are set up with their respective traits.

## Usage

### Prerequisites

-   Your `User` model (specified in `config/news-feed-generator.php`) must use the `Ijideals\Followable\Followable` trait.
-   Your `Post` model (specified in `config/news-feed-generator.php`) must be the one that users create and that you want to appear in the feed. It should have an `author` relationship (polymorphic or direct, matching what `FeedAggregatorService` expects).

### API Endpoint

The package provides one main API endpoint:

*   **`GET /api/v1/feed`** (The prefix `api/v1/feed` is configurable via `news-feed-generator.route_prefix`)
    *   **Authentication:** Requires an authenticated user (uses `auth:api` middleware).
    *   **Parameters:**
        *   `page` (optional): The page number for pagination (e.g., `?page=2`).
    *   **Response:** A paginated JSON response containing the posts for the user's feed, ranked by relevance. Each post object will typically include its data, author information, and like/comment counts (used for ranking). The `score` attribute might also be present if not removed before sending the response.

    Example Response (structure remains similar, order changes based on ranking):
    ```json
    {
        "data": [
            {
                "id": 101,
                "content": "This is the latest post from someone I follow!",
                "author_id": 2,
                "author_type": "App\\Models\\User",
                "created_at": "2023-10-28T12:00:00.000000Z",
                "updated_at": "2023-10-28T12:00:00.000000Z",
                "author": {
                    "id": 2,
                    "name": "Followed User Name",
                    // ... other user fields
                },
                "likes_count": 5,
                "comments_count": 2
            },
            // ... more post items
        ],
        "links": {
            "first": "http://localhost/api/v1/feed?page=1",
            "last": "http://localhost/api/v1/feed?page=3",
            "prev": null,
            "next": "http://localhost/api/v1/feed?page=2"
        },
        "meta": {
            "current_page": 1,
            "from": 1,
            "last_page": 3,
            "path": "http://localhost/api/v1/feed",
            "per_page": 15,
            "to": 15,
            "total": 40
        }
    }
    ```

### Clearing Cache

The `FeedCacheManager` provides methods to clear cache. Cache invalidation primarily relies on TTL. For more aggressive cache busting (e.g., when a user follows someone new, or a new post is made by a followed user that should appear high in the feed), you would typically dispatch an event and have a listener clear the relevant user's feed cache (especially page 1).

### Localization (L10n)

The package is structured to support localization, though the API currently returns raw data or standard HTTP error messages which are handled by Laravel's default localization or the `SetLocale` middleware.

*   **Publishing Language Files (for future use):**
    ```bash
    php artisan vendor:publish --provider="Ijideals\NewsFeedGenerator\Providers\NewsFeedGeneratorServiceProvider" --tag="news-feed-generator-lang"
    ```
    This will publish `en/news-feed-generator.php` and `fr/news-feed-generator.php` to `lang/vendor/news-feed-generator`. These are minimal for now.

## Testing

```bash
# From your Laravel application root
./vendor/bin/phpunit packages/ijideals/news-feed-generator/tests
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) (to be created) for details.

## Security

If you discover any security related issues, please email jules@ai.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) (to be created) for more information.
```

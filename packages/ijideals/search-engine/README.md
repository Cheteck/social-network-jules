# Laravel Search Engine (ijideals/search-engine)

This package provides global search functionality for your Laravel application, built on top of Laravel Scout. It allows you to easily configure and search across multiple Eloquent models. The default setup uses Laravel Scout's `database` driver.

## Features

-   Search across multiple configured Eloquent models.
-   Uses Laravel Scout for indexing and searching.
-   Configurable list of searchable models and default fields.
-   API endpoint to perform searches with query term and model type filters.
-   Paginated search results.
-   Easy to switch Scout driver (e.g., to Meilisearch, Algolia) by changing Laravel Scout's configuration.

## Installation

1.  **Require Laravel Scout (if not already installed):**
    ```bash
    composer require laravel/scout
    ```

2.  **Require this package:**
    ```bash
    composer require ijideals/search-engine
    ```
    (If local, ensure path repository is set in main `composer.json`)

3.  **Service Providers:**
    Laravel's auto-discovery should detect `Laravel\Scout\ScoutServiceProvider` and `Ijideals\SearchEngine\Providers\SearchEngineServiceProvider`. If not, add them to `config/app.php`.

4.  **Publish Scout Configuration:**
    ```bash
    php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
    ```
    This creates `config/scout.php`.

5.  **Configure Scout Driver:**
    In your `.env` file, set the Scout driver. For the database driver:
    ```env
    SCOUT_DRIVER=database
    ```
    You can also configure this in `config/scout.php`. The default table name for the database driver is `scout_index`.

6.  **Create `scout_index` Table (for Database Driver):**
    If you are using the `database` driver, you need a table to store the search indexes. A migration for this table was included in the setup of this Search Engine feature (located in `database/migrations` of the main application). Run your migrations:
    ```bash
    php artisan migrate
    ```

7.  **Publish Search Engine Package Configuration (Optional):**
    ```bash
    php artisan vendor:publish --provider="Ijideals\SearchEngine\Providers\SearchEngineServiceProvider" --tag="search-engine-config"
    ```
    This publishes `config/search-engine.php` where you can define searchable models, default searchable fields, API route prefix, and pagination settings.

## Usage

### 1. Make Your Models Searchable

For each Eloquent model you want to make searchable:

*   Add the `Laravel\Scout\Searchable` trait.
*   Implement the `toSearchableArray()` method to define what data from the model should be indexed.
*   Optionally, implement `searchableAs()` to customize the index name (useful if not using the `database` driver's single table approach or for clarity).

**Example: `App\Models\User`**
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use Searchable;
    // ... other traits and model code

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    // Optional: Define a specific index name for this model
    // public function searchableAs(): string
    // {
    //     return config('scout.prefix').'users_index';
    // }
}
```

**Example: `Ijideals\SocialPosts\Models\Post`**
```php
<?php

namespace Ijideals\SocialPosts\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
// ... other imports

class Post extends Model // ... implements other contracts
{
    use Searchable;
    // ... other traits and model code

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            // 'author_name' => $this->author?->name, // Example: index related data
        ];
    }
}
```
Make sure to list these models in `config/search-engine.php`:
```php
// config/search-engine.php
'searchable_models' => [
    'user' => \App\Models\User::class,
    'post' => \Ijideals\SocialPosts\Models\Post::class,
],
```

### 2. Indexing Data

After making your models searchable, you need to import your existing data into the search index:

```bash
php artisan scout:import "App\Models\User"
php artisan scout:import "Ijideals\SocialPosts\Models\Post"
# Repeat for all your searchable models
```
New and updated models will be automatically kept in sync with the index by Scout.

To flush an index:
```bash
php artisan scout:flush "App\Models\User"
```

### 3. API Endpoint

The package provides a global search API endpoint:

*   **`GET /api/v1/search`** (The prefix `api/v1/search` is configurable via `search-engine.route_prefix`)
    *   **Authentication:** Not required by default for this endpoint (can be added by wrapping routes).
    *   **Query Parameters:**
        *   `q` (required): The search term.
        *   `types` (optional): A comma-separated string of model aliases (e.g., `user,post`) to search within. If omitted, searches all models defined in `config/search-engine.php -> searchable_models`.
        *   `{alias}_page` (optional): Page number for a specific model type's results (e.g., `users_page=2`, `posts_page=1`).
        *   `per_page_{alias}` (optional): Number of items per page for a specific model type. Defaults to `search-engine.pagination_items`.

    **Example Request:**
    `GET /api/v1/search?q=hello%20world&types=user,post&user_page=1&post_page=1`

    **Example Response:**
    ```json
    {
        "query": "hello world",
        "results": {
            "user": {
                "data": [ /* ... user results ... */ ],
                "pagination": { /* ... pagination info for users ... */ }
            },
            "post": {
                "data": [ /* ... post results ... */ ],
                "pagination": { /* ... pagination info for posts ... */ }
            }
        }
    }
    ```
    If no results are found for a type, or the type is not searched, its key might be absent or its `data` array will be empty. If the overall query yields no results, the main `results` object might be empty.

### 4. Localization

This package's controller currently returns standard HTTP error messages or data directly. For translated error messages (e.g., "Search query cannot be empty"), ensure your application's `SetLocale` middleware is active and provide translations in your main application's language files for generic messages if needed.

## Testing

```bash
# From your Laravel application root
./vendor/bin/phpunit packages/ijideals/search-engine/tests
```

## Future Considerations

-   Support for more advanced Scout drivers (Algolia, Meilisearch) for better relevance and features.
-   More sophisticated result formatting using API Resources.
-   Weighted search fields or boosting certain models.
-   Frontend components to consume the search API.

## Contributing & License

Standard MIT License. Contributions welcome.
```

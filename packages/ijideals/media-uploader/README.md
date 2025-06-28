# Laravel Media Uploader (ijideals/media-uploader)

This package provides a flexible system for uploading and associating media files (images initially, extendable for others) with your Eloquent models in a Laravel application. It supports multiple file storage disks, image optimizations via Intervention Image, and custom media collections.

## Features

- Associate media with any Eloquent model (polymorphic relations).
- Support for multiple file disks (local, S3, etc.).
- Configurable media collections with specific rules (disk, path, validation, single-file).
- Image optimization (resize, quality, auto-orient) using Intervention Image.
- API endpoints for uploading, listing, deleting, and reordering media.
- `HasMedia` trait for easy integration with models.
- File validation (size, MIME type) via configuration and service layer.
- Media ordering within collections.
- Basic internationalization for API messages.

## Installation

1.  **Require the package (and Intervention Image):**
    ```bash
    composer require ijideals/media-uploader intervention/image
    ```
    (If local, ensure path repository is set in main `composer.json` for `ijideals/media-uploader`)

2.  **Service Provider:**
    Laravel's auto-discovery should detect `Ijideals\MediaUploader\Providers\MediaUploaderServiceProvider` and `Intervention\Image\ImageServiceProvider`. If not, add them to `config/app.php`.

3.  **Publish & Run Migrations:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\MediaUploader\Providers\MediaUploaderServiceProvider" --tag="media-uploader-migrations"
    php artisan migrate
    ```

4.  **Publish Configuration:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\MediaUploader\Providers\MediaUploaderServiceProvider" --tag="media-uploader-config"
    ```
    This publishes `config/media-uploader.php`. Review and customize, especially `default_disk`, `image_optimizations`, and define your `collections`.

5.  **Configure Filesystems & Storage Link:**
    Ensure your desired storage disks are configured in `config/filesystems.php`. If using the `public` disk, run `php artisan storage:link`.

## Usage

### 1. Prepare Your Model

Add the `Ijideals\MediaUploader\Concerns\HasMedia` trait to any Eloquent model:

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Ijideals\MediaUploader\Concerns\HasMedia;

class Post extends Model {
    use HasMedia;
    // ...
}
```

### 2. Uploading Media

Use the `addMedia` method from the `HasMedia` trait:

```php
use Illuminate\Http\Request;
use App\Models\Post;

public function uploadImage(Request $request, Post $post) {
    if ($request->hasFile('image_file')) {
        try {
            $mediaItem = $post->addMedia(
                $request->file('image_file'), // UploadedFile instance
                'post_images',                // Collection name
                null,                         // Disk (null to use collection/default)
                null,                         // Directory (null for collection/default)
                'custom-name',                // File name (null for auto-generated UUID)
                ['alt' => 'My Image Alt']     // Custom properties
            );
            // $mediaItem is the created Media model instance or false
        } catch (\Exception $e) {
            // Handle exception (e.g., validation error from MediaUploaderService)
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
```
The `MediaUploaderService` handles validation (size, MIME type) based on global and collection-specific configurations.

### 3. Retrieving Media

```php
$post = Post::find(1);

// Get the first media item from a collection
$image = $post->getFirstMedia('post_images');
if ($image) {
    $url = $image->getFullUrl(); // Full URL to the (potentially optimized) file
    // $thumbUrl = $image->getUrl('thumbnail'); // If you implement named conversions
}

// Get all media from a collection
$galleryImages = $post->getMedia('gallery'); // Returns an Eloquent Collection
foreach ($galleryImages as $img) {
    echo $img->getFullUrl();
}
```
You can define accessors on your model for specific collections, e.g., `getAvatarUrlAttribute()` on `User`.

### 4. Deleting Media

```php
// Delete a specific media item by its ID
$mediaItem = $post->getFirstMedia('post_images');
if ($mediaItem) {
    $post->clearMediaById($mediaItem->id); // Or $mediaItem->delete();
}

// Clear all media from a collection
$post->clearMediaCollection('post_images');
```
**Note:** Actual file deletion from disk should be handled by an event listener on the `Media` model's `deleting` event (see "Customizing File Deletion" in the main README of this package for an example).

### 5. Media Collections

Define collections in `config/media-uploader.php` for specific rules:
```php
// config/media-uploader.php
'collections' => [
    'user' => [ // Model type alias (from morphMap or snake_case class name)
        'avatar' => [
            'disk' => 'public',
            'directory' => 'avatars/{model_id}',
            'single_file' => true, // Important for avatars
            // ... other rules like max_file_size, allowed_mime_types
        ],
    ],
    'post' => [
        'images' => [ /* ... */ ]
    ]
],
```

### 6. API Routes

Default prefix: `api/v1/media` (configurable in `config/media-uploader.php`).
*   `GET /{media_id}`: Show a specific media item.
*   `DELETE /{media_id}`: Delete a media item. (Auth required)
*   `POST /model/{model_type_alias}/{model_id}`: Upload media for a model. (Auth required)
*   `GET /model/{model_type_alias}/{model_id}/collection/{collection_name?}`: List media for a model's collection.
*   `POST /model/{model_type_alias}/{model_id}/collection/{collection_name}/reorder`: Reorder media. (Auth required)

### 7. Localization (L10n)

API response messages and validation errors from the service are translatable.

*   **Publishing Language Files:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\MediaUploader\Providers\MediaUploaderServiceProvider" --tag="media-uploader-lang"
    ```
    Customize in `lang/vendor/media-uploader/{locale}/media-uploader.php`.

*   **Supported Locales:** `en`, `fr` by default.

## Testing

```bash
./vendor/bin/phpunit packages/ijideals/media-uploader/tests
```

<?php

namespace Ijideals\MediaUploader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'manipulations' => 'array',
        'properties' => 'array', // For custom properties like alt text, etc.
        'order_column' => 'integer',
    ];

    /**
     * Get the parent model that this media item belongs to.
     */
    public function model(): MorphTo
    {
        return $this->morphTo(); // column names: model_type, model_id
    }

    /**
     * Get the full URL to the media file.
     *
     * @return string|null
     */
    public function getFullUrl(): ?string
    {
        if (!$this->disk || !$this->path) {
            return null;
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the URL for a specific conversion/manipulation if available.
     * For now, this is a placeholder. Actual implementation depends on how
     * manipulations are stored and named.
     *
     * @param string $conversionName
     * @return string|null
     */
    public function getUrl(string $conversionName = ''): ?string
    {
        if (empty($conversionName)) {
            return $this->getFullUrl();
        }

        // Example: if manipulations are stored like 'thumb' => 'path/to/thumb.jpg'
        // This part needs to be fleshed out based on how conversions are handled.
        // For a simple start, we might assume a naming convention like original_filename_thumb.ext
        // or store paths to conversions in the 'manipulations' array.

        // Placeholder logic:
        if (isset($this->manipulations[$conversionName]['path']) && isset($this->manipulations[$conversionName]['disk'])) {
             return Storage::disk($this->manipulations[$conversionName]['disk'])->url($this->manipulations[$conversionName]['path']);
        }

        // Fallback if specific conversion URL logic isn't implemented yet or found
        // This might involve constructing a path based on a convention.
        // e.g., pathinfo($this->path, PATHINFO_DIRNAME) . '/' . pathinfo($this->path, PATHINFO_FILENAME) . '_' . $conversionName . '.' . pathinfo($this->path, PATHINFO_EXTENSION);

        return $this->getFullUrl(); // Fallback to original if conversion not found or logic not implemented
    }

    /**
     * Get the file size in a human-readable format.
     * @return string
     */
    public function getHumanReadableSize(): string
    {
        $size = $this->size; // in bytes
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }


    /**
     * Create a new factory instance for the model.
     * (Optional, but good for testing)
     */
    protected static function newFactory()
    {
        // Attempt to use a package-specific factory if it exists
        $factory = \Ijideals\MediaUploader\Database\Factories\MediaFactory::class;
        if (class_exists($factory)) {
            return $factory::new();
        }
        // Basic fallback if no specific factory is defined for the package
        return new class extends \Illuminate\Database\Eloquent\Factories\Factory {
            protected $model = Media::class;
            public function definition() {
                return [
                    'name' => $this->faker->word . '.' . $this->faker->fileExtension,
                    'file_name' => $this->faker->systemFileName,
                    'path' => 'uploads/' . $this->faker->systemFileName,
                    'disk' => config('media-uploader.default_disk', 'public'),
                    'mime_type' => $this->faker->mimeType,
                    'size' => $this->faker->numberBetween(1000, 5000000),
                    'collection_name' => 'default',
                    // model_id and model_type would be set by the caller
                ];
            }
        };
    }
}

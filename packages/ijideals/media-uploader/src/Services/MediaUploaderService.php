<?php

namespace Ijideals\MediaUploader\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image; // Or ImageManagerStatic if using v2 without facade
use Ijideals\MediaUploader\Models\Media; // Corrected namespace if Media model is directly under Models
// use Exception; // Not used yet, but good for custom exceptions

class MediaUploaderService
{
    protected $file;
    protected $model;
    protected $collectionName = 'default';
    protected $disk;
    protected $directory;
    protected $fileName = null; // Custom file name (without extension)
    protected $mediaProperties = [];
    protected $collectionSettings = [];

    public function __construct()
    {
        // Load default settings from config
        $this->disk = config('media-uploader.default_disk');
        $this->directory = config('media-uploader.default_directory');
        $this->collectionSettings = config('media-uploader.collections.default', []);
    }

    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function setCollection(string $collectionName): self
    {
        $this->collectionName = $collectionName;
        // Load collection specific settings if they exist
        $modelTypeAlias = array_search(get_class($this->model), \Illuminate\Database\Eloquent\Relations\Relation::morphMap()) ?: Str::snake(class_basename($this->model));

        $specificCollectionSettings = config("media-uploader.collections.{$modelTypeAlias}.{$collectionName}");
        if (is_array($specificCollectionSettings)) {
            $this->collectionSettings = array_merge($this->collectionSettings, $specificCollectionSettings);
        }

        $this->disk = $this->collectionSettings['disk'] ?? config('media-uploader.default_disk');
        $this->directory = $this->collectionSettings['directory'] ?? config('media-uploader.default_directory');

        return $this;
    }

    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = Str::slug($fileName); // Sanitize the custom name
        return $this;
    }

    public function setProperties(array $properties): self
    {
        $this->mediaProperties = $properties;
        return $this;
    }

    /**
     * Process the upload and store the media.
     * @return Media|false
     * @throws \Exception
     */
    public function upload(): Media|false
    {
        if (!$this->file || !$this->model) {
            throw new \Exception(__('media-uploader::media-uploader.file_or_model_not_set'));
        }

        $this->validateFile(); // This will throw translated exceptions

        $originalName = $this->file->getClientOriginalName();
        $originalExtension = $this->file->getClientOriginalExtension();
        $mimeType = $this->file->getMimeType();
        $size = $this->file->getSize();

        $finalFileName = $this->fileName ? $this->fileName . '.' . $originalExtension : $this->generateUniqueFileName($originalExtension);

        $parsedDirectory = $this->parseDirectoryPlaceholders($this->directory);
        $path = rtrim($parsedDirectory, '/') . '/' . $finalFileName;

        $manipulations = [];

        // Image processing (if it's an image and optimizations are enabled)
        $isImage = Str::startsWith($mimeType, 'image/');
        $optimizationSettings = $this->collectionSettings['image_optimizations'] ?? config('media-uploader.image_optimizations', []);

        if ($isImage && !empty($optimizationSettings['enabled'])) {
            try {
                $image = Image::make($this->file->getRealPath());

                if ($optimizationSettings['auto_orient'] ?? false) {
                    $image->orientate();
                }

                $maxWidth = $optimizationSettings['max_width'] ?? null;
                $maxHeight = $optimizationSettings['max_height'] ?? null;

                if ($maxWidth || $maxHeight) {
                    $image->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize(); // Prevent upsizing
                    });
                }

                $quality = $optimizationSettings['quality'] ?? 85;
                $imageStream = $image->stream(null, $quality)->__toString();

                // Store the optimized image
                Storage::disk($this->disk)->put($path, $imageStream);
                $size = strlen($imageStream); // Update size to optimized size

                // TODO: Handle WebP conversion if enabled
                // TODO: Handle keeping original image if configured

                $manipulations['optimized'] = [
                    'width' => $image->width(),
                    'height' => $image->height(),
                    'quality' => $quality,
                ];

            } catch (\Exception $e) {
                // Log image processing error and fallback to storing original
                $errorMessage = __('media-uploader::media-uploader.processing_error', ['originalName' => $originalName, 'error' => $e->getMessage()]);
                error_log("MediaUploaderService: " . $errorMessage);
                // Optionally, rethrow with a generic or translated message if you don't want to expose detailed error
                // For now, we proceed to store original if optimization fails.
                Storage::disk($this->disk)->putFileAs($parsedDirectory, $this->file, $finalFileName);
            }
        } else {
            // Not an image or optimizations disabled, store original
            Storage::disk($this->disk)->putFileAs($parsedDirectory, $this->file, $finalFileName);
        }


        $mediaModelClass = config('media-uploader.media_model', Media::class);
        $media = $mediaModelClass::create([
            'model_type' => $this->model->getMorphClass(),
            'model_id' => $this->model->getKey(),
            'collection_name' => $this->collectionName,
            'name' => $originalName,
            'file_name' => $finalFileName,
            'path' => $path,
            'disk' => $this->disk,
            'mime_type' => $mimeType,
            'size' => $size,
            'manipulations' => $manipulations,
            'properties' => $this->mediaProperties,
            // 'order_column' => $this->getNextOrderColumn(), // Handled by HasMedia trait typically
        ]);

        return $media;
    }

    protected function validateFile(): void
    {
        $maxSize = ($this->collectionSettings['max_file_size'] ?? config('media-uploader.max_file_size', 5120)) * 1024; // in bytes
        $allowedMimes = $this->collectionSettings['allowed_mime_types'] ?? config('media-uploader.allowed_mime_types', []);

        if ($this->file->getSize() > $maxSize) {
            throw new \Exception(__('media-uploader::media-uploader.file_too_large', ['maxSizeKB' => $maxSize / 1024]));
        }

        $currentMime = $this->file->getMimeType();
        if (!empty($allowedMimes) && !in_array($currentMime, $allowedMimes)) {
             // Check for wildcard MIME types like 'image/*'
            $generalType = explode('/', $currentMime)[0] . '/*';
            if (!in_array($generalType, $allowedMimes)) {
                throw new \Exception(__('media-uploader::media-uploader.invalid_mime_type', [
                    'mimeType' => $currentMime,
                    'allowedTypes' => implode(', ', $allowedMimes)
                ]));
            }
        }
    }

    protected function generateUniqueFileName(string $extension): string
    {
        return Str::uuid()->toString() . '.' . $extension;
    }

    protected function parseDirectoryPlaceholders(string $directoryPattern): string
    {
        return str_replace(
            ['{model_type}', '{model_id}', '{collection_name}', '{uuid}', '{random_string}'],
            [
                Str::snake(class_basename($this->model)),
                $this->model->getKey(),
                $this->collectionName,
                Str::uuid()->toString(),
                Str::random(10)
            ],
            $directoryPattern
        );
    }

    // Example for managing single file collections (e.g., avatar)
    public function handleSingleFileCollection(Media $newMedia): void
    {
        if ($this->collectionSettings['single_file'] ?? false) {
            // Delete previous media in this collection for this model
            $mediaModelClass = config('media-uploader.media_model', Media::class);
            $oldMedia = $mediaModelClass::where('model_type', $newMedia->model_type)
                ->where('model_id', $newMedia->model_id)
                ->where('collection_name', $newMedia->collection_name)
                ->where('id', '!=', $newMedia->id)
                ->get();

            foreach ($oldMedia as $item) {
                Storage::disk($item->disk)->delete($item->path);
                // TODO: Delete manipulations/conversions if they are stored separately
                $item->delete();
            }
        }
    }
}

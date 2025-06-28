<?php

namespace Ijideals\MediaUploader\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Ijideals\MediaUploader\Models\Media; // Supposant que le modèle Media existe dans ce package
use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    /**
     * Get all of the model's media.
     */
    public function media(): MorphMany
    {
        // Assumant que le modèle Media est \Ijideals\MediaUploader\Models\Media
        // et qu'il a une colonne 'model_type' et 'model_id' pour la relation polymorphique.
        return $this->morphMany(config('media-uploader.media_model', Media::class), 'model');
    }

    /**
     * Attach a file to the model.
     *
     * @param UploadedFile $file
     * @param string $collectionName
     * @param string|null $disk
     * @return Media|false
     */
    public function addMedia(UploadedFile $file, string $collectionName = 'default', string $disk = null)
    {
        if (!config('media-uploader.media_model', Media::class)) {
            // Log or throw exception: Media model not configured
            return false;
        }

        $disk = $disk ?? config('media-uploader.default_disk', 'public');
        $directory = config("media-uploader.collections.{$collectionName}.directory", $collectionName);

        // Ensure model has an ID for path generation if needed, or use a generic path.
        $pathDirectory = $this->exists ? str_replace('{model_id}', $this->getKey(), $directory) : $directory;

        $filename = $file->hashName(); // Generate a unique name
        $path = $file->store($pathDirectory, $disk);

        if ($path) {
            /** @var Media $media */
            $media = $this->media()->create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $filename, // ou basename($path)
                'mime_type' => $file->getMimeType(),
                'path' => $path,
                'disk' => $disk,
                'collection_name' => $collectionName,
                'size' => $file->getSize(),
            ]);
            return $media;
        }
        return false;
    }

    /**
     * Get the first media item of a collection.
     *
     * @param string $collectionName
     * @return Media|null
     */
    public function getFirstMedia(string $collectionName = 'default'): ?Model
    {
        return $this->media()->where('collection_name', $collectionName)->first();
    }

    /**
     * Get all media items of a collection.
     *
     * @param string $collectionName
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMedia(string $collectionName = 'default')
    {
        return $this->media()->where('collection_name', $collectionName)->get();
    }

    // Placeholder for clearing media. More specific methods might be needed.
    public function clearMediaCollection(string $collectionName = 'default')
    {
        $this->media()->where('collection_name', $collectionName)->get()->each(function ($media) {
            // Storage::disk($media->disk)->delete($media->path); // Delete actual file
            // $media->delete(); // Delete DB record - this should be handled by Media model's deleting event
            $media->delete();
        });
    }
}

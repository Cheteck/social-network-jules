<?php

namespace Ijideals\MediaUploader\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Ijideals\MediaUploader\Models\Media;
use Ijideals\MediaUploader\Concerns\HasMedia as HasMediaTrait; // To check if model uses the trait
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['indexByModel']); // Allow guests to view media if public
    }

    /**
     * Store a newly uploaded media file and associate it with a model.
     *
     * @param Request $request
     * @param string $modelTypeAlias Alias for the model type (e.g., 'post', 'user')
     * @param int $modelId ID of the model instance
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeForModel(Request $request, string $modelTypeAlias, int $modelId)
    {
        $request->validate([
            'file' => 'required|file', // Consider adding mimes, max size validation here too, though service will also validate
            'collection_name' => 'sometimes|string|max:255',
            // 'alt_text' => 'sometimes|string|max:255', // Example custom property
        ]);

        $model = $this->resolveModel($modelTypeAlias, $modelId);
        if (!$model || !$this->modelUsesHasMediaTrait($model)) {
            return response()->json(['message' => __('media-uploader::media-uploader.model_not_found')], 404);
        }

        // Basic authorization: Check if authenticated user can update the model (owns it, or has permission)
        // This is a generic check; more specific policies might be needed.
        if (method_exists($model, 'user_id') && $model->user_id != Auth::id()) {
            if(!Auth::user()->can('update', $model)) { // Check for a policy
                 // return response()->json(['message' => __('media-uploader::media-uploader.unauthorized_action')], 403);
            }
        }


        $collectionName = $request->input('collection_name', 'default');
        $properties = $request->except(['file', 'collection_name']); // Pass other inputs as properties

        try {
            $media = $model->addMedia(
                $request->file('file'),
                $collectionName,
                null, // disk (use collection/default)
                null, // directory (use collection/default)
                null, // file_name (generate unique)
                $properties
            );

            if ($media) {
                return response()->json(['message' => __('media-uploader::media-uploader.upload_success'), 'media' => $media], 201);
            }
            return response()->json(['message' => __('media-uploader::media-uploader.upload_failed')], 500);

        } catch (\Exception $e) {
            // Log::error("Media upload failed: " . $e->getMessage());
            // Check if the message is one of our translatable validation messages from the service
            if (in_array($e->getMessage(), array_keys(trans('media-uploader::media-uploader')))) {
                 return response()->json(['message' => __($e->getMessage())], 422);
            }
            return response()->json(['message' => $e->getMessage()], 422); // Or a generic error
        }
    }

    /**
     * List media for a specific model and collection.
     *
     * @param Request $request
     * @param string $modelTypeAlias
     * @param int $modelId
     * @param string $collectionName
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexByModel(Request $request, string $modelTypeAlias, int $modelId, string $collectionName = 'default')
    {
        $model = $this->resolveModel($modelTypeAlias, $modelId);
        if (!$model || !$this->modelUsesHasMediaTrait($model)) {
            return response()->json(['message' => __('media-uploader::media-uploader.model_not_found')], 404);
        }

        $mediaItems = $model->getMedia($collectionName);
        return response()->json($mediaItems);
    }

    /**
     * Get a specific media item by its ID.
     *
     * @param int $mediaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $mediaId)
    {
        $mediaModelClass = config('media-uploader.media_model', Media::class);
        $mediaItem = $mediaModelClass::find($mediaId);

        if (!$mediaItem) {
            return response()->json(['message' => __('media-uploader::media-uploader.media_not_found')], 404);
        }
        // Add authorization if needed: e.g., user can only see their own media or public media
        return response()->json($mediaItem);
    }


    /**
     * Delete a specific media item.
     *
     * @param int $mediaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $mediaId)
    {
        $mediaModelClass = config('media-uploader.media_model', Media::class);
        $mediaItem = $mediaModelClass::find($mediaId);

        if (!$mediaItem) {
            return response()->json(['message' => __('media-uploader::media-uploader.media_not_found')], 404);
        }

        // Authorization: Ensure user owns the media or the parent model, or has general permission.
        // This is a simplified check. A policy (e.g., MediaPolicy) would be better.
        $parentModel = $mediaItem->model; // Get the owning model
        if ($parentModel && method_exists($parentModel, 'user_id') && $parentModel->user_id != Auth::id()) {
             if(!Auth::user()->can('update', $parentModel) && !Auth::user()->can('delete', $mediaItem)) { // Check policies
                // return response()->json(['message' => __('media-uploader::media-uploader.unauthorized_action')], 403);
             }
        } elseif (!$parentModel && !Auth::user()->can('delete', $mediaItem)) { // Media not attached or different policy
            // return response()->json(['message' => __('media-uploader::media-uploader.unauthorized_action')], 403);
        }


        try {
            // The HasMedia trait on the parent model has clearMediaById,
            // or we can call delete directly on the Media model.
            // Calling delete on Media model is more direct if we already fetched it.
            // The Media model's own 'deleting' event should handle file removal from disk.
            if ($mediaItem->delete()) {
                return response()->json(['message' => __('media-uploader::media-uploader.delete_success')]);
            }
            return response()->json(['message' => __('media-uploader::media-uploader.delete_failed')], 500);
        } catch (\Exception $e) {
            // Log::error("Media deletion failed: " . $e->getMessage());
            return response()->json(['message' => __('media-uploader::media-uploader.delete_error', ['error' => $e->getMessage()])], 500);
        }
    }

    /**
     * Reorder media items for a specific model and collection.
     * Expects an array of media IDs in the desired order.
     */
    public function reorder(Request $request, string $modelTypeAlias, int $modelId, string $collectionName = 'default')
    {
        $request->validate([
            'ordered_media_ids' => 'required|array',
            'ordered_media_ids.*' => 'integer',
        ]);

        $model = $this->resolveModel($modelTypeAlias, $modelId);
        if (!$model || !$this->modelUsesHasMediaTrait($model)) {
            return response()->json(['message' => __('media-uploader::media-uploader.model_not_found')], 404);
        }

        // Authorization (similar to storeForModel)
        if (method_exists($model, 'user_id') && $model->user_id != Auth::id()) {
            if(!Auth::user()->can('update', $model)) {
                 // return response()->json(['message' => __('media-uploader::media-uploader.unauthorized_action')], 403);
            }
        }

        $model->updateMediaOrder($request->input('ordered_media_ids'), $collectionName);

        return response()->json(['message' => __('media-uploader::media-uploader.reorder_success')]);
    }


    /**
     * Resolve the model instance from type alias and ID.
     */
    protected function resolveModel(string $typeAlias, int $id): ?Model
    {
        $modelClass = null;
        // Try morphMap first
        $morphMap = \Illuminate\Database\Eloquent\Relations\Relation::morphMap();
        if (isset($morphMap[$typeAlias])) {
            $modelClass = $morphMap[$typeAlias];
        } else {
            // Fallback to configured namespace + studly case of alias
            $studlyAlias = Str::studly($typeAlias);
            $potentialClass = rtrim(config('media-uploader.model_namespace', 'App\\Models\\'), '\\') . '\\' . $studlyAlias;
            if (class_exists($potentialClass)) {
                $modelClass = $potentialClass;
            }
        }

        if (!$modelClass || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        try {
            return $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Check if the given model uses the HasMedia trait.
     */
    protected function modelUsesHasMediaTrait(Model $model): bool
    {
        return in_array(HasMediaTrait::class, class_uses_recursive(get_class($model)));
    }
}

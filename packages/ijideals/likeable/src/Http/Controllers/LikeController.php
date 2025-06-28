<?php

namespace Ijideals\Likeable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ijideals\Likeable\Contracts\Likeable as LikeableContract;
use Ijideals\Likeable\Contracts\Liker as LikerContract;

class LikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api'); // Ensure user is authenticated
    }

    /**
     * Like an item.
     *
     * @param  \Illuminate\Http\Request  $request (can be used if params are in body)
     * @param  string  $likeableType  The short type of the model (e.g., 'post', 'comment')
     * @param  int  $likeableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function like(Request $request, string $likeableType, int $likeableId): JsonResponse
    {
        $user = Auth::user();
        // $user should implement LikerContract and use CanLike trait.
        if (!$user instanceof LikerContract) {
             return response()->json(['message' => __('likeable::likeable.user_model_error')], 500);
        }

        $model = $this->resolveLikeableModel($likeableType, $likeableId);
        if (!$model) {
            return response()->json(['message' => __('likeable::likeable.entity_not_found')], 404);
        }

        if ($user->hasLiked($model)) {
            $like = $model->likes()->where('user_id', $user->getKey())->first();
            return response()->json([
                'message' => __('likeable::likeable.already_liked'),
                'like' => $like,
                'likes_count' => $model->likes_count,
            ], 409); // Conflict
        }

        $like = $user->like($model);

        if ($like) {
            return response()->json([
                'message' => __('likeable::likeable.successfully_liked'),
                'like' => $like,
                'likes_count' => $model->refresh()->likes_count,
            ], 201); // Created
        }

        return response()->json(['message' => __('likeable::likeable.could_not_like')], 500);
    }

    /**
     * Unlike an item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $likeableType
     * @param  int  $likeableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlike(Request $request, string $likeableType, int $likeableId): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof LikerContract) {
            return response()->json(['message' => __('likeable::likeable.user_model_error')], 500);
        }

        $model = $this->resolveLikeableModel($likeableType, $likeableId);
        if (!$model) {
            return response()->json(['message' => __('likeable::likeable.entity_not_found')], 404);
        }

        if (!$user->hasLiked($model)) {
            return response()->json(['message' => __('likeable::likeable.not_liked_yet')], 409); // Conflict or 404
        }

        if ($user->unlike($model)) {
            return response()->json([
                'message' => __('likeable::likeable.successfully_unliked'),
                'likes_count' => $model->refresh()->likes_count,
            ], 200);
        }

        return response()->json(['message' => __('likeable::likeable.could_not_unlike')], 500);
    }

    /**
     * Toggle like status for an item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $likeableType
     * @param  int  $likeableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, string $likeableType, int $likeableId): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof LikerContract) {
            return response()->json(['message' => __('likeable::likeable.user_model_error')], 500);
        }

        $model = $this->resolveLikeableModel($likeableType, $likeableId);
        if (!$model) {
            return response()->json(['message' => __('likeable::likeable.entity_not_found')], 404);
        }

        $result = $user->toggleLike($model);
        $model->refresh(); // Refresh to get updated likes_count

        if ($result === true) { // Unliked
            return response()->json([
                'message' => __('likeable::likeable.toggled_unliked'),
                'status' => 'unliked',
                'likes_count' => $model->likes_count,
            ], 200);
        } elseif ($result instanceof Model) { // Liked (result is the Like model instance)
            return response()->json([
                'message' => __('likeable::likeable.toggled_liked'),
                'status' => 'liked',
                'like' => $result,
                'likes_count' => $model->likes_count,
            ], 200); // 201 if a new like was created, 200 if state changed. Let's use 200 for toggle.
        }

        return response()->json(['message' => __('likeable::likeable.could_not_toggle')], 500);
    }


    /**
     * Resolve the likeable model instance.
     *
     * @param string $typeAlias
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model|null implementing LikeableContract
     */
    protected function resolveLikeableModel(string $typeAlias, int $id): ?Model
    {
        $modelClass = null;
        $configMorphMap = config('likeable.morph_map', []);

        if (!empty($configMorphMap) && isset($configMorphMap[$typeAlias])) {
            $modelClass = $configMorphMap[$typeAlias];
        } else {
            // Fallback to Laravel's global morph map if available
            if (class_exists(\Illuminate\Database\Eloquent\Relations\Relation::class)) {
                $globalMorphMap = \Illuminate\Database\Eloquent\Relations\Relation::morphMap();
                if (isset($globalMorphMap[$typeAlias])) {
                    $modelClass = $globalMorphMap[$typeAlias];
                }
            }
        }

        // If still not found, and as a last resort (less safe, usually for testing/dev)
        // you could try to infer it, but it's better to require explicit mapping.
        // Example (naive, adjust for your app's namespace):
        // if (!$modelClass) {
        //     $studlyType = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $typeAlias)));
        //     $potentialModelClass = "App\\Models\\{$studlyType}";
        //     if (class_exists($potentialModelClass)) {
        //         $modelClass = $potentialModelClass;
        //     }
        // }

        if (!$modelClass || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        try {
            $modelInstance = $modelClass::findOrFail($id);
            // Ensure the resolved model implements LikeableContract
            if ($modelInstance instanceof LikeableContract) {
                return $modelInstance;
            }
            return null; // Does not implement the contract
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }
}

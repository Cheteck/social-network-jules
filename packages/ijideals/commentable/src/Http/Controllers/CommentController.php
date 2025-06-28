<?php

namespace Ijideals\Commentable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Ijideals\Commentable\Models\Comment;
use Ijideals\Commentable\Contracts\CommentableContract;
use Ijideals\Commentable\Contracts\CommenterContract;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index']); // Allow guests to view comments
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param string $commentableType
     * @param int $commentableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $commentableType, int $commentableId)
    {
        $model = $this->resolveCommentableModel($commentableType, $commentableId);
        if (!$model) {
            return response()->json(['message' => __('commentable::commentable.entity_not_found')], 404);
        }

        // Add pagination, filtering, sorting as needed
        $comments = $model->comments()
            ->with(config('commentable.nested_comments', true) ? 'replies' : []) // Eager load replies if nested
            ->approved() // Only show approved comments by default
            ->paginate($request->input('per_page', 15));

        return response()->json($comments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $commentableType
     * @param  int  $commentableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $commentableType, int $commentableId)
    {
        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'sometimes|nullable|integer|exists:'.config('commentable.table_name', 'comments').',id',
        ]);

        $user = Auth::user();
        if (!$user instanceof CommenterContract) {
            return response()->json(['message' => __('commentable::commentable.user_model_error')], 403);
        }

        $model = $this->resolveCommentableModel($commentableType, $commentableId);
        if (!$model) {
            return response()->json(['message' => __('commentable::commentable.entity_not_found')], 404);
        }

        $parentComment = null;
        if ($request->filled('parent_id') && config('commentable.nested_comments', true)) {
            $commentModelClass = config('commentable.comment_model', Comment::class);
            $parentComment = $commentModelClass::find($request->parent_id);
            if (!$parentComment ||
                $parentComment->commentable_id != $model->getKey() ||
                $parentComment->commentable_type != $model->getMorphClass()) {
                return response()->json(['message' => __('commentable::commentable.parent_comment_error')], 422);
            }
        }

        $comment = $user->comment($model, $request->input('content'), $parentComment);

        if ($comment) {
            // Might need to load commenter relation: $comment->load('commenter');
            return response()->json(['message' => __('commentable::commentable.successfully_posted'), 'comment' => $comment->load('commenter')], 201);
        }
        return response()->json(['message' => __('commentable::commentable.could_not_post')], 500);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $commentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $commentId)
    {
        $request->validate(['content' => 'required|string']);

        $user = Auth::user();
        if (!$user instanceof CommenterContract) {
            return response()->json(['message' => __('commentable::commentable.user_model_error')], 403);
        }

        $commentModelClass = config('commentable.comment_model', Comment::class);
        $comment = $commentModelClass::find($commentId);

        if (!$comment) {
            return response()->json(['message' => __('commentable::commentable.comment_not_found')], 404);
        }
        if ($comment->user_id != $user->getKey()) {
            return response()->json(['message' => __('commentable::commentable.ownership_error')], 403);
        }
        // Add policy/gate check here for more complex authorization: Gate::authorize('update', $comment);


        if ($user->updateComment($comment, $request->input('content'))) {
            return response()->json(['message' => __('commentable::commentable.successfully_updated'), 'comment' => $comment->refresh()->load('commenter')]);
        }
        return response()->json(['message' => __('commentable::commentable.could_not_update')], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $commentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $commentId)
    {
        $user = Auth::user();
         if (!$user instanceof CommenterContract) {
            return response()->json(['message' => __('commentable::commentable.user_model_error')], 403);
        }

        $commentModelClass = config('commentable.comment_model', Comment::class);
        $comment = $commentModelClass::find($commentId);

        if (!$comment) {
            return response()->json(['message' => __('commentable::commentable.comment_not_found')], 404);
        }
        if ($comment->user_id != $user->getKey()) {
            // Or allow admin/moderator to delete: Gate::authorize('delete', $comment);
            return response()->json(['message' => __('commentable::commentable.ownership_error')], 403);
        }

        if ($user->deleteComment($comment)) {
            return response()->json(['message' => __('commentable::commentable.successfully_deleted')]);
        }
        return response()->json(['message' => __('commentable::commentable.could_not_delete')], 500);
    }

    /**
     * Resolve the commentable model instance.
     */
    protected function resolveCommentableModel(string $typeAlias, int $id): ?Model
    {
        $modelClass = null;
        $configMorphMap = config('commentable.morph_map', []);

        if (!empty($configMorphMap) && isset($configMorphMap[$typeAlias])) {
            $modelClass = $configMorphMap[$typeAlias];
        } elseif (class_exists(\Illuminate\Database\Eloquent\Relations\Relation::class)) {
            $globalMorphMap = \Illuminate\Database\Eloquent\Relations\Relation::morphMap();
            if (isset($globalMorphMap[$typeAlias])) $modelClass = $globalMorphMap[$typeAlias];
        }

        if (!$modelClass || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        try {
            $modelInstance = $modelClass::findOrFail($id);
            return ($modelInstance instanceof CommentableContract) ? $modelInstance : null;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }
}

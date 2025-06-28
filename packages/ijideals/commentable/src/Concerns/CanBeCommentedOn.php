<?php

namespace Ijideals\Commentable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ijideals\Commentable\Models\Comment;

trait CanBeCommentedOn
{
    /**
     * Boot the CanBeCommentedOn trait.
     * Automatically deletes comments when the commentable model is deleted.
     */
    protected static function bootCanBeCommentedOn()
    {
        static::deleting(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return; // Don't delete comments if it's a soft delete and not force deleting
            }
            // If the model uses soft deletes, this will only run on forceDelete.
            // If it doesn't use soft deletes, it runs on regular delete.
            $model->comments()->get()->each(function ($comment) {
                // If comments themselves use soft deletes, this will soft delete them.
                // If not, it will hard delete.
                // If nested comments are enabled, deleting a parent comment should cascade.
                $comment->delete();
            });
        });
    }

    /**
     * Get all comments for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments(): MorphMany
    {
        $relation = $this->morphMany(config('commentable.comment_model', Comment::class), 'commentable');

        if (config('commentable.nested_comments', true)) {
            // When fetching comments for a model, usually we only want top-level comments.
            // Replies can be fetched via the 'replies' relation on each Comment model.
            $relation->whereNull('parent_id');
        }

        $order = config('commentable.default_order', 'asc');
        return $relation->orderBy('created_at', $order);
    }

    /**
     * Add a comment to this model.
     *
     * @param string $content The content of the comment.
     * @param \Illuminate\Database\Eloquent\Model|int|null $user The user posting the comment. Defaults to Auth::user().
     * @param \Ijideals\Commentable\Models\Comment|null $parent The parent comment if this is a reply.
     * @return Comment|false The created Comment model or false on failure.
     */
    public function addComment(string $content, $user = null, ?Comment $parent = null)
    {
        $userInstance = $this->resolveCommenter($user);
        // Allow anonymous comments if $userInstance is null and user_id is nullable in comments table.

        $commentModelClass = config('commentable.comment_model', Comment::class);
        /** @var Comment $comment */
        $comment = new $commentModelClass([
            'content' => $content,
            'user_id' => $userInstance ? $userInstance->getKey() : null,
            // 'approved_at' => // Handle approval logic here or in an observer/event
        ]);

        if (config('commentable.nested_comments', true) && $parent instanceof $commentModelClass) {
            if ($parent->commentable_id !== $this->getKey() || $parent->commentable_type !== $this->getMorphClass()) {
                // Parent comment does not belong to this model
                return false;
            }
            // Consider max depth if configured
            // $currentDepth = 0; $tempParent = $parent;
            // while($tempParent && $tempParent->parent_id) { $currentDepth++; $tempParent = $tempParent->parent; }
            // if(config('commentable.max_depth', 0) > 0 && $currentDepth >= config('commentable.max_depth')) return false;

            $comment->parent_id = $parent->getKey();
        }

        $savedComment = $this->comments()->save($comment);

        if ($savedComment) {
            // Dispatch CommentPosted event
            $commentPostedEventClass = config('commentable.events.comment_posted', \Ijideals\Commentable\Events\CommentPosted::class);
            if (class_exists($commentPostedEventClass)) {
                event(new $commentPostedEventClass($savedComment->load(['commenter', 'commentable', 'parent.commenter']))); // Eager load relations needed by listeners
            }
        }
        return $savedComment;
    }

    /**
     * Get the count of all comments (including replies, if not filtered).
     * To get only top-level comments count: $model->comments()->count()
     * To get all comments including replies: $model->allComments()->count()
     */
    public function getCommentsCountAttribute(): int
    {
        // This will count only top-level comments due to the scope in comments() relation
        if (array_key_exists('comments_count', $this->attributes)) {
             return (int) $this->attributes['comments_count'];
        }
        return $this->comments()->count();
    }

    /**
     * Get all comments for this model, including replies (recursive).
     * This is a more direct way to get all comments regardless of nesting for counting.
     */
    public function allComments(): MorphMany
    {
         $relation = $this->morphMany(config('commentable.comment_model', Comment::class), 'commentable');
         $order = config('commentable.default_order', 'asc');
         return $relation->orderBy('created_at', $order);
    }


    /**
     * Resolve the user instance.
     *
     * @param Model|int|null $user
     * @return Model|null
     */
    protected function resolveCommenter($user = null): ?Model
    {
        if (is_null($user) && auth()->check()) {
            return auth()->user();
        }

        if (is_numeric($user)) {
            $userModelClass = config('commentable.user_model', \App\Models\User::class);
            return $userModelClass::find($user);
        }

        return $user instanceof Model ? $user : null;
    }
}

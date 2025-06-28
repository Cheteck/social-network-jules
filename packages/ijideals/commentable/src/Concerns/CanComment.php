<?php

namespace Ijideals\Commentable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ijideals\Commentable\Models\Comment;
use Ijideals\Commentable\Contracts\CommentableContract;

trait CanComment
{
    /**
     * Get all comments made by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commentsMade(): HasMany
    {
        return $this->hasMany(config('commentable.comment_model', Comment::class), 'user_id');
    }

    /**
     * Post a comment on a commentable model.
     *
     * @param \Ijideals\Commentable\Contracts\CommentableContract $commentable The model to comment on.
     * @param string $content The content of the comment.
     * @param \Ijideals\Commentable\Models\Comment|null $parent The parent comment if this is a reply.
     * @return Comment|false
     */
    public function comment(CommentableContract $commentable, string $content, ?Comment $parent = null)
    {
        if (!$commentable instanceof Model || !method_exists($commentable, 'addComment')) {
            return false; // Not a valid commentable model
        }

        return $commentable->addComment(content: $content, user: $this, parent: $parent);
    }

    /**
     * Update one of the user's comments.
     *
     * @param Comment $comment The comment to update.
     * @param string $newContent The new content for the comment.
     * @return bool
     */
    public function updateComment(Comment $comment, string $newContent): bool
    {
        if ($comment->user_id != $this->getKey()) {
            return false; // User does not own this comment
        }

        // Potentially add more checks, e.g., if editing is allowed after a certain time.
        return $comment->update(['content' => $newContent]);
    }

    /**
     * Delete one of the user's comments.
     *
     * @param Comment $comment The comment to delete.
     * @return bool|null
     */
    public function deleteComment(Comment $comment)
    {
        if ($comment->user_id != $this->getKey()) {
            return false; // User does not own this comment
        }
        // If comment has replies, they might be soft deleted or re-parented depending on policy.
        // The Comment model's deleting event or here can handle that.
        // For now, basic deletion. SoftDeletes trait on Comment model will handle soft/hard delete.
        return $comment->delete();
    }

    /**
     * Get the number of comments this user has made.
     *
     * @return int
     */
    public function getCommentsMadeCountAttribute(): int
    {
        if (array_key_exists('comments_made_count', $this->attributes)) {
            return (int) $this->attributes['comments_made_count'];
        }
        return $this->commentsMade()->count();
    }
}

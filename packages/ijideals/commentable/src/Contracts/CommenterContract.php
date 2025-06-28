<?php

namespace Ijideals\Commentable\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Ijideals\Commentable\Models\Comment;

interface CommenterContract
{
    /**
     * Get all comments made by this user.
     */
    public function commentsMade(): HasMany;

    /**
     * Post a comment on a commentable model.
     *
     * @param \Ijideals\Commentable\Contracts\CommentableContract $commentable
     * @param string $content
     * @param \Ijideals\Commentable\Models\Comment|null $parent
     * @return Comment|false
     */
    public function comment(CommentableContract $commentable, string $content, ?Comment $parent = null);

    /**
     * Update one of the user's comments.
     *
     * @param Comment $comment
     * @param string $newContent
     * @return bool
     */
    public function updateComment(Comment $comment, string $newContent): bool;

    /**
     * Delete one of the user's comments.
     *
     * @param Comment $comment
     * @return bool|null
     */
    public function deleteComment(Comment $comment);

    /**
     * Get the number of comments this user has made.
     */
    public function getCommentsMadeCountAttribute(): int;
}

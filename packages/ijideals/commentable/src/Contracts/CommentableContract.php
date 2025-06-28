<?php

namespace Ijideals\Commentable\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ijideals\Commentable\Models\Comment;

interface CommentableContract
{
    /**
     * Get all (top-level) comments for this model.
     */
    public function comments(): MorphMany;

    /**
     * Add a comment to this model.
     *
     * @param string $content
     * @param Model|int|null $user
     * @param Comment|null $parent
     * @return Comment|false
     */
    public function addComment(string $content, $user = null, ?Comment $parent = null);

    /**
     * Get the count of (top-level) comments.
     */
    public function getCommentsCountAttribute(): int;

    /**
     * Get all comments for this model, including replies.
     */
    public function allComments(): MorphMany;
}

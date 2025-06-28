<?php

namespace Ijideals\Likeable\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Likeable
{
    /**
     * Get all likes for this model.
     */
    public function likes(): MorphMany;

    /**
     * Like this model.
     *
     * @param Model|int|null $user
     * @return Model|false
     */
    public function addLike($user = null);

    /**
     * Unlike this model.
     *
     * @param Model|int|null $user
     * @return bool
     */
    public function removeLike($user = null): bool;

    /**
     * Toggle like status for this model.
     *
     * @param Model|int|null $user
     * @return Model|bool
     */
    public function toggleLike($user = null);

    /**
     * Check if the model is liked by a given user.
     *
     * @param Model|int|null $user
     * @return bool
     */
    public function isLikedBy($user = null): bool;

    /**
     * Get the count of likes.
     *
     * @return int
     */
    public function getLikesCountAttribute(): int;

    /**
     * Get the users who liked the model.
     */
    public function likers();

    /**
     * Delete all likes for this model.
     *
     * @return void
     */
    public function removeLikes();
}

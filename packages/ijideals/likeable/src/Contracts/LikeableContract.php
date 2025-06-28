<?php

namespace Ijideals\Likeable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface LikeableContract
{
    /**
     * Get the likers of this model.
     * Should return a MorphToMany relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function likers(): MorphToMany;

    /**
     * Check if the model is liked by a specific user.
     *
     * @param \App\Models\User|null $user
     * @return bool
     */
    public function isLikedBy($user = null): bool;

    /**
     * Get the number of likes for this model.
     *
     * @return int
     */
    public function likesCount(): int;
}

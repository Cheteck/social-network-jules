<?php

namespace Ijideals\Likeable\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Liker
{
    /**
     * Get all like records made by this user.
     */
    public function likesMade(): HasMany;

    /**
     * Like a likeable model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return \Ijideals\Likeable\Models\Like|false
     */
    public function like(Likeable $likeable);

    /**
     * Unlike a likeable model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return bool
     */
    public function unlike(Likeable $likeable): bool;

    /**
     * Toggle like for a likeable model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return \Ijideals\Likeable\Models\Like|bool|false
     */
    public function toggleLike(Likeable $likeable);

    /**
     * Check if the user has liked a specific model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return bool
     */
    public function hasLiked(Likeable $likeable): bool;

    /**
     * Get the number of likes this user has made.
     * @return int
     */
    public function getLikesMadeCountAttribute(): int;

    /**
     * Get all models of a specific type that this user has liked.
     *
     * @param string $modelClass
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLikedItems(string $modelClass);
}

<?php

namespace Ijideals\Likeable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait CanLike
{
    /**
     * Get the like model class name.
     *
     * @return string
     */
    protected function likeModel(): string
    {
        return config('likeable.like_model', \Ijideals\Likeable\Models\Like::class);
    }

    /**
     * Get all likes made by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany($this->likeModel(), 'user_id');
    }

    /**
     * Like a given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $likeable
     * @return \Illuminate\Database\Eloquent\Model|bool
     */
    public function like(Model $likeable)
    {
        if (!method_exists($likeable, 'likeBy')) {
            // Optionally throw an exception if the model is not "CanBeLiked"
            return false;
        }

        return $likeable->likeBy($this);
    }

    /**
     * Unlike a given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $likeable
     * @return bool
     */
    public function unlike(Model $likeable): bool
    {
        if (!method_exists($likeable, 'unlikeBy')) {
            // Optionally throw an exception if the model is not "CanBeLiked"
            return false;
        }

        return $likeable->unlikeBy($this);
    }

    /**
     * Check if this user has liked a given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $likeable
     * @return bool
     */
    public function hasLiked(Model $likeable): bool
    {
        if (!method_exists($likeable, 'isLikedBy')) {
            return false;
        }

        return $likeable->isLikedBy($this);
    }

    /**
     * Toggle like for a given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $likeable
     * @return \Illuminate\Database\Eloquent\Model|bool
     */
    public function toggleLike(Model $likeable)
    {
        if ($this->hasLiked($likeable)) {
            return $this->unlike($likeable);
        }

        return $this->like($likeable);
    }

    /**
     * Get all models of a certain type that this user has liked.
     * Example: $user->getLikedModels(Post::class)
     *
     * @param string $modelClass The class name of the likeable model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLikedModels(string $modelClass)
    {
        return $this->likes()
            ->where('likeable_type', (new $modelClass)->getMorphClass())
            ->with('likeable')
            ->get()
            ->map(function ($like) {
                return $like->likeable;
            });
    }
}

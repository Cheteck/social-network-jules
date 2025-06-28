<?php

namespace Ijideals\Likeable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Ijideals\Likeable\Models\Like;
use Ijideals\Likeable\Contracts\Likeable as LikeableContract;
// LikerContract would be defined if we create an interface for this trait.
// use Ijideals\Likeable\Contracts\Liker as LikerContract;

trait CanLike // implements LikerContract
{
    /**
     * Get all like records made by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function likesMade(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('likeable.like_model', Like::class), 'user_id');
    }

    /**
     * Like a likeable model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return \Ijideals\Likeable\Models\Like|false
     */
    public function like(LikeableContract $likeable)
    {
        if (!$likeable instanceof Model || !method_exists($likeable, 'addLike')) {
            // Ensure $likeable is an Eloquent Model and uses CanBeLiked trait (or implements LikeableContract correctly)
            return false;
        }
        return $likeable->addLike($this);
    }

    /**
     * Unlike a likeable model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return bool
     */
    public function unlike(LikeableContract $likeable): bool
    {
        if (!$likeable instanceof Model || !method_exists($likeable, 'removeLike')) {
            return false;
        }
        return $likeable->removeLike($this);
    }

    /**
     * Toggle like for a likeable model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return \Ijideals\Likeable\Models\Like|bool|false
     */
    public function toggleLike(LikeableContract $likeable)
    {
        if (!$likeable instanceof Model || !method_exists($likeable, 'toggleLike')) {
            return false;
        }
        return $likeable->toggleLike($this);
    }

    /**
     * Check if the user has liked a specific model.
     *
     * @param \Ijideals\Likeable\Contracts\Likeable $likeable
     * @return bool
     */
    public function hasLiked(LikeableContract $likeable): bool
    {
        if (!$likeable instanceof Model || !method_exists($likeable, 'isLikedBy')) {
            return false;
        }
        return $likeable->isLikedBy($this);
    }

    /**
     * Get the number of likes this user has made.
     * This can be accessed via $user->likes_made_count
     * @return int
     */
    public function getLikesMadeCountAttribute(): int
    {
        // If using withCount on likesMade relationship
        if (array_key_exists('likes_made_count', $this->attributes)) {
            return (int) $this->attributes['likes_made_count'];
        }
        return $this->likesMade()->count();
    }

    /**
     * Get all models of a specific type that this user has liked.
     *
     * @param string $modelClass The class name of the likeable model (e.g., Post::class).
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLikedItems(string $modelClass)
    {
        // Ensure $modelClass is a valid Eloquent model that uses CanBeLiked trait
        if (!is_subclass_of($modelClass, Model::class)) {
            return collect(); // Or throw an exception
        }

        $likeableType = (new $modelClass)->getMorphClass();

        return $modelClass::whereHas('likes', function ($query) use ($likeableType) {
            $query->where('user_id', $this->getKey())
                  ->where('likeable_type', $likeableType);
        })->get();


        // Alternative way, potentially less efficient if you need full models directly
        // but useful if you already have likesMade loaded.
        /*
        return $this->likesMade()
            ->where('likeable_type', (new $modelClass)->getMorphClass())
            ->with('likeable') // Eager load the liked models
            ->get()
            ->map(function ($like) {
                return $like->likeable;
            })
            ->filter(); // Remove any nulls if a liked model was deleted
        */
    }
}

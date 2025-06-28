<?php

namespace Ijideals\Likeable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ijideals\Likeable\Events\Liked;
use Ijideals\Likeable\Events\Unliked;

trait CanBeLiked
{
    /**
     * Get the like model class name.
     *
     * @return string
     */
    public static function likeModel(): string
    {
        return config('likeable.like_model', \Ijideals\Likeable\Models\Like::class);
    }

    /**
     * Get the user model class name.
     *
     * @return string
     */
    public static function userModel(): string
    {
        return config('likeable.user_model', \App\Models\User::class);
    }

    /**
     * Boot the CanBeLiked trait.
     *
     * @return void
     */
    public static function bootCanBeLiked()
    {
        static::deleting(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }
            $model->likes()->delete();
        });
    }

    /**
     * Relationship with Like model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(static::likeModel(), 'likeable');
    }

    /**
     * Like this model by a user.
     *
     * @param Model|int|null $user
     * @return \Illuminate\Database\Eloquent\Model|bool
     */
    public function likeBy(Model|int|null $user = null)
    {
        $userModel = static::userModel();
        $userId = $user instanceof Model ? $user->getKey() : $user;
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false; // Or throw an exception
        }

        if ($this->isLikedBy($userId)) {
            return false; // Already liked
        }

        $like = $this->likes()->create([
            'user_id' => $userId,
        ]);

        event(new Liked($this, $userModel::find($userId)));

        return $like;
    }

    /**
     * Unlike this model by a user.
     *
     * @param Model|int|null $user
     * @return bool
     */
    public function unlikeBy(Model|int|null $user = null)
    {
        $userModel = static::userModel();
        $userId = $user instanceof Model ? $user->getKey() : $user;
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false; // Or throw an exception
        }

        $like = $this->likes()
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();
            event(new Unliked($this, $userModel::find($userId)));
            return true;
        }

        return false;
    }

    /**
     * Check if the model is liked by a user.
     *
     * @param Model|int|null $user
     * @return bool
     */
    public function isLikedBy(Model|int|null $user = null): bool
    {
        $userId = $user instanceof Model ? $user->getKey() : $user;
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        return $this->likes()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get the total number of likes.
     *
     * @return int
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Scope a query to include models liked by a given user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Model|int $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereLikedBy($query, Model|int $user)
    {
        $userId = $user instanceof Model ? $user->getKey() : $user;
        $likeModelTable = (new (static::likeModel()))->getTable();

        return $query->whereHas('likes', function ($q) use ($userId, $likeModelTable) {
            $q->where("{$likeModelTable}.user_id", $userId);
        });
    }
}

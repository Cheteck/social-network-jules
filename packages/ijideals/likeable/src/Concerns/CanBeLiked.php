<?php

namespace Ijideals\Likeable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ijideals\Likeable\Models\Like;
use Illuminate\Support\Facades\Auth;
use Ijideals\Likeable\Contracts\Likeable as LikeableContract;

trait CanBeLiked // implements LikeableContract (cannot implement directly in trait, but class using it should)
{
    /**
     * Boot the CanBeLiked trait.
     * Automatically deletes likes when the model is deleted.
     */
    protected static function bootCanBeLiked()
    {
        static::deleting(function (Model $model) {
            // Check if the model uses SoftDeletes and is being force deleted,
            // or if it doesn't use SoftDeletes at all.
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return; // Don't delete likes if it's a soft delete and not force deleting
            }
            $model->removeLikes();
        });
    }

    /**
     * Get all likes for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(config('likeable.like_model', Like::class), 'likeable');
    }

    /**
     * Like this model.
     *
     * @param Model|int|null $user The user who liked the model. Defaults to authenticated user.
     * @return Model|false Returns the Like model instance on success, false if already liked or user not provided.
     */
    public function addLike($user = null)
    {
        $userInstance = $this->resolveUser($user);
        if (!$userInstance) {
            return false;
        }

        if ($this->isLikedBy($userInstance)) {
            return $this->likes()->where('user_id', $userInstance->getKey())->first(); // Already liked, return existing like
        }

        $like = app(config('likeable.like_model', Like::class));
        $like->user_id = $userInstance->getKey();
        // $like->user()->associate($userInstance); // Alternative if user_id is not fillable

        $this->likes()->save($like);

        // Dispatch ModelLiked event
        $modelLikedEventClass = config('likeable.events.model_liked', \Ijideals\Likeable\Events\ModelLiked::class);
        if (class_exists($modelLikedEventClass)) {
            event(new $modelLikedEventClass($like));
        }

        return $like;
    }

    /**
     * Unlike this model.
     *
     * @param Model|int|null $user The user who unliked the model. Defaults to authenticated user.
     * @return bool True on success, false if not liked by the user or user not provided.
     */
    public function removeLike($user = null): bool
    {
        $userInstance = $this->resolveUser($user);
        if (!$userInstance) {
            return false;
        }

        $like = $this->likes()
            ->where('user_id', $userInstance->getKey())
            ->first();

        if (!$like) {
            return false; // Not liked by this user
        }

        $result = $like->delete();

        // Dispatch event if configured
        // if (config('likeable.broadcast_events') && class_exists(config('likeable.events.model_unliked'))) {
        //     event(new (config('likeable.events.model_unliked'))($this, $userInstance));
        // }
        return (bool) $result;
    }

    /**
     * Toggle like status for this model.
     *
     * @param Model|int|null $user The user. Defaults to authenticated user.
     * @return Model|bool Returns Like model if liked, true if unliked, false on error.
     */
    public function toggleLike($user = null)
    {
        $userInstance = $this->resolveUser($user);
        if (!$userInstance) {
            return false;
        }

        if ($this->isLikedBy($userInstance)) {
            return $this->removeLike($userInstance) ? true : false; // Return true for unlike success
        }

        return $this->addLike($userInstance);
    }

    /**
     * Check if the model is liked by a given user.
     *
     * @param Model|int|null $user The user. Defaults to authenticated user.
     * @return bool
     */
    public function isLikedBy($user = null): bool
    {
        $userInstance = $this->resolveUser($user);
        if (!$userInstance) {
            return false;
        }

        return $this->likes()
            ->where('user_id', $userInstance->getKey())
            ->exists();
    }

    /**
     * Get the count of likes.
     * With Eager Loading: $model->loadCount('likes') -> $model->likes_count
     *
     * @return int
     */
    public function getLikesCountAttribute(): int
    {
        if (array_key_exists('likes_count', $this->attributes)) {
            return (int) $this->attributes['likes_count'];
        }
        return $this->likes()->count();
    }

    /**
     * Get the number of likes for this model.
     * (Required by LikeableContract)
     *
     * @return int
     */
    public function likesCount(): int
    {
        return $this->getLikesCountAttribute(); // Reuse the accessor logic
    }

    /**
     * Get the users who liked the model.
     * This will query the users table based on the user_id in the likes table.
     * This method is defined by the LikeableContract.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function likers(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        // This defines a many-to-many polymorphic relationship back to the users (likers).
        // 'user' is the name of the relationship on the intermediate Like model (if it had one, or conceptual name).
        // 'likes' is the pivot table name.
        // 'likeable_id' is the foreign key on the 'likes' table that points to the current model (the "likeable").
        // 'user_id' is the foreign key on the 'likes' table that points to the User model (the "liker").
        return $this->morphToMany(
            config('likeable.user_model', \App\Models\User::class), // Related model (User)
            'user', // This is a placeholder name for the relationship, will be ignored if not used by Eloquent internally for this type of relation.
                    // What matters are the table and key names.
                    // Often, the name of the inverse morph relation ('liker' or 'user') might be used.
                    // Let's use 'liker' as it's more descriptive of the role in this context.
            'likes', // The intermediate (pivot) table name
            'likeable_id', // Foreign key on 'likes' table for the current model (e.g., post_id if this model is Post)
                           // This is derived from $this->getForeignKey() if not specified.
            'user_id',     // Foreign key on 'likes' table for the User model.
            $this->getKeyName(), // Parent key on the current model (e.g., id for Post)
            (app(config('likeable.user_model', \App\Models\User::class)))->getKeyName(), // Parent key on the User model (e.g., id for User)
        );
    }

    /**
     * Delete all likes for this model.
     *
     * @return void
     */
    public function removeLikes()
    {
        $this->likes()->delete();
    }


    /**
     * Resolve the user instance from various input types.
     *
     * @param Model|int|null $user
     * @return Model|null
     */
    protected function resolveUser($user = null): ?Model
    {
        if (is_null($user)) {
            $user = Auth::user();
        }

        if (is_numeric($user)) {
            $userModelClass = config('likeable.user_model', \App\Models\User::class);
            $user = $userModelClass::find($user);
        }

        return $user instanceof Model ? $user : null;
    }
}

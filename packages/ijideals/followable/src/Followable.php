<?php

namespace Ijideals\Followable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Followable
{
    /**
     * Allow a user to follow this model.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|Model|null $user
     * @return void
     */
    public function follow($user = null)
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return;
        }

        if ($this->isFollowing($user)) {
            return;
        }

        $this->followers()->attach($user->getKey());

        // Dispatch UserFollowed event
        $userFollowedEventClass = config('followable.events.user_followed', \Ijideals\Followable\Events\UserFollowed::class);
        if (class_exists($userFollowedEventClass)) {
            // $this is the model being followed. $user is the follower.
            event(new $userFollowedEventClass($user, $this));
        }
    }

    /**
     * Allow a user to unfollow this model.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|Model|null $user
     * @return void
     */
    public function unfollow($user = null)
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return;
        }

        $this->followers()->detach($user->getKey());

        // Dispatch UserUnfollowed event
        $userUnfollowedEventClass = config('followable.events.user_unfollowed', \Ijideals\Followable\Events\UserUnfollowed::class);
        if (class_exists($userUnfollowedEventClass)) {
            // $this is the model being unfollowed. $user is the unfollower.
            event(new $userUnfollowedEventClass($user, $this));
        }
    }

    /**
     * Toggle follow status for a user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|Model|null $user
     * @return void
     */
    public function toggleFollow($user = null)
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return;
        }

        $this->followers()->toggle($user->getKey());
    }

    /**
     * Check if the current model is followed by a given user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|Model|null $user
     * @return bool
     */
    public function isFollowing($user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return false;
        }

        return $this->followers()->where(config('auth.providers.users.model', \App\Models\User::class).'.id', $user->getKey())->exists();
    }

    /**
     * Get the users that follow this model.
     * Using morphToMany for the followers relationship.
     */
    public function followers()
    {
        // Assumes the User model is in App\Models\User
        // If not, this should be configurable, perhaps via a config file or a static property on the trait.
        return $this->morphToMany(config('auth.providers.users.model', \App\Models\User::class), 'followable', 'followers', 'followable_id', 'user_id');
    }

    /**
     * Get the models that this user follows.
     * This method should ideally be on the User model or a trait used by the User model.
     * For a model to be "followable", it uses this Followable trait.
     * For a model to be a "follower" (e.g., User), it would need its own relationship.
     * Let's define a 'followings' relationship assuming the User model will also use a similar mechanism
     * or this method will be primarily used on a User model that also uses this trait.
     */
    public function followings()
    {
        // This defines what entities this model (e.g. a User) is following.
        // It requires a 'user_id' column on the 'followers' table that links back to the follower (e.g., User).
        // And 'followable_id' and 'followable_type' for the model being followed.
        return $this->morphedByMany(static::class, 'followable', 'followers', 'user_id', 'followable_id')
            ->where('followable_type', $this->getMorphClass());
    }


    /**
     * Get the count of followers.
     *
     * @return int
     */
    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    /**
     * Get the count of followings.
     *
     * @return int
     */
    public function getFollowingsCountAttribute(): int
    {
        // This requires the 'followings' relationship to be correctly set up.
        // If this trait is used on a User model, 'followings' would list other users/entities that this user follows.
        if (method_exists($this, 'followings')) {
             return $this->followings()->count();
        }
        return 0;
    }
}

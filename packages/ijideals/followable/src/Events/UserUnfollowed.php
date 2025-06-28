<?php

namespace Ijideals\Followable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class UserUnfollowed
{
    use Dispatchable, SerializesModels;

    /**
     * The user who performed the unfollow.
     * @var \Illuminate\Database\Eloquent\Model
     */
    public Model $unfollower; // User who performed the unfollow

    /**
     * The user who was unfollowed.
     * @var \Illuminate\Database\Eloquent\Model
     */
    public Model $unfollowed; // User who was unfollowed

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $unfollower
     * @param \Illuminate\Database\Eloquent\Model $unfollowed
     * @return void
     */
    public function __construct(Model $unfollower, Model $unfollowed)
    {
        $this->unfollower = $unfollower;
        $this->unfollowed = $unfollowed;
    }
}

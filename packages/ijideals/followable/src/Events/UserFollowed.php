<?php

namespace Ijideals\Followable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class UserFollowed
{
    use Dispatchable, SerializesModels;

    /**
     * The user who performed the follow.
     * @var \Illuminate\Database\Eloquent\Model
     */
    public Model $follower;

    /**
     * The user who was followed.
     * @var \Illuminate\Database\Eloquent\Model
     */
    public Model $followed;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $follower
     * @param \Illuminate\Database\Eloquent\Model $followed
     * @return void
     */
    public function __construct(Model $follower, Model $followed)
    {
        $this->follower = $follower;
        $this->followed = $followed;
    }
}

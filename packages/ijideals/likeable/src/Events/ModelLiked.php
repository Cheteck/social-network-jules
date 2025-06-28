<?php

namespace Ijideals\Likeable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ijideals\Likeable\Models\Like; // Assuming your Like model is here

class ModelLiked
{
    use Dispatchable, SerializesModels;

    /**
     * The like instance.
     *
     * @var \Ijideals\Likeable\Models\Like
     */
    public Like $like;

    /**
     * Create a new event instance.
     *
     * @param  \Ijideals\Likeable\Models\Like  $like
     * @return void
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }
}

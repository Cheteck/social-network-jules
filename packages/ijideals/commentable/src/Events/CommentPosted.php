<?php

namespace Ijideals\Commentable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ijideals\Commentable\Models\Comment;

class CommentPosted
{
    use Dispatchable, SerializesModels;

    /**
     * The comment instance.
     *
     * @var \Ijideals\Commentable\Models\Comment
     */
    public Comment $comment;

    /**
     * Create a new event instance.
     *
     * @param  \Ijideals\Commentable\Models\Comment  $comment
     * @return void
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }
}

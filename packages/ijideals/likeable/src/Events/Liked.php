<?php

namespace Ijideals\Likeable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Liked
{
    use Dispatchable, SerializesModels;

    public Model $likeable;
    public ?Model $liker;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $likeable
     * @param \Illuminate\Database\Eloquent\Model|null $liker
     * @return void
     */
    public function __construct(Model $likeable, ?Model $liker)
    {
        $this->likeable = $likeable;
        $this->liker = $liker;
    }
}

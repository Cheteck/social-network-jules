<?php

namespace IJIDeals\MentionSystem\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IJIDeals\MentionSystem\Models\Mention;

class UserMentioned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Mention $mention;

    /**
     * Create a new event instance.
     *
     * @param \IJIDeals\MentionSystem\Models\Mention $mention
     * @return void
     */
    public function __construct(Mention $mention)
    {
        $this->mention = $mention;
    }
}

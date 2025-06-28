<?php

namespace Ijideals\NotificationSystem\Listeners;

use Ijideals\NotificationSystem\Services\NotificationCreationService;
use Illuminate\Contracts\Queue\ShouldQueue; // Optional
use Illuminate\Support\Facades\Log;

// Assume an event like this exists:
// namespace Ijideals\Followable\Events;
// class UserFollowed {
//     public $follower; // User who performed the follow
//     public $followed; // User who was followed
//     public function __construct($follower, $followed) {
//         $this->follower = $follower;
//         $this->followed = $followed;
//     }
// }

class SendNewFollowerNotificationListener // implements ShouldQueue // Optional
{
    protected NotificationCreationService $notificationCreator;

    public function __construct(NotificationCreationService $notificationCreator)
    {
        $this->notificationCreator = $notificationCreator;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event Example: \Ijideals\Followable\Events\UserFollowed
     * @return void
     */
    public function handle($event)
    {
        if (!isset($event->follower) || !isset($event->followed)) {
            Log::warning('[NotificationSystem] SendNewFollowerNotificationListener: Event structure invalid or missing follower/followed data.', ['event_class' => get_class($event)]);
            return;
        }

        $follower = $event->follower; // The user who initiated the follow
        $followedUser = $event->followed; // The user who was followed

        // It's implicit that $follower->id != $followedUser->id because one cannot follow oneself
        // (assuming the Followable package enforces this).

        if ($follower && $followedUser) {
            $this->notificationCreator->createNotification(
                $followedUser->id, // Recipient ID (the user who gained a follower)
                'new_follower',    // Notification type key
                [
                    'follower_id' => $follower->id,
                    'follower_name' => $follower->name, // Or a display name attribute
                    // No specific item here, the "item" is the user being followed.
                ]
            );
        }
    }
}

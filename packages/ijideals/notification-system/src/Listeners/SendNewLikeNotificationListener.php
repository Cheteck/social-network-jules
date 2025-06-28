<?php

namespace Ijideals\NotificationSystem\Listeners;

use Ijideals\NotificationSystem\Services\NotificationCreationService;
use Illuminate\Contracts\Queue\ShouldQueue; // Optional: if you want listeners to be queued
use Illuminate\Support\Facades\Log;

// Assume an event like this exists or will be created in the Likeable package:
// namespace Ijideals\Likeable\Events;
// class ModelLiked { public $like; public function __construct($like) { $this->like = $like; } }

class SendNewLikeNotificationListener // implements ShouldQueue // Optional
{
    protected NotificationCreationService $notificationCreator;

    public function __construct(NotificationCreationService $notificationCreator)
    {
        $this->notificationCreator = $notificationCreator;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event Example: \Ijideals\Likeable\Events\ModelLiked
     * @return void
     */
    public function handle($event)
    {
        // Ensure the event has the expected structure ($event->like)
        if (!isset($event->like) || !property_exists($event->like, 'likeable') || !property_exists($event->like, 'user')) {
            Log::warning('[NotificationSystem] SendNewLikeNotificationListener: Event structure invalid or missing like data.', ['event_class' => get_class($event)]);
            return;
        }

        $like = $event->like;
        $likeable = $like->likeable; // The model that was liked (e.g., Post)
        $liker = $like->user;    // The user who performed the like

        // Don't notify if someone likes their own content
        if (!property_exists($likeable, 'author_id') && !property_exists($likeable, 'user_id')) { // Ensure likeable has an owner
             Log::info('[NotificationSystem] SendNewLikeNotificationListener: Likeable model does not have an identifiable author/owner.', ['likeable_type' => get_class($likeable)]);
            return;
        }

        // Determine the owner of the likeable item
        // This logic might need to be more robust depending on how ownership is defined on likeable models
        $itemOwnerId = null;
        if (property_exists($likeable, 'author_id') && $likeable->author_id) { // e.g. for Post model from social-posts
            $itemOwnerId = $likeable->author_id;
        } elseif (property_exists($likeable, 'user_id') && $likeable->user_id) { // Generic user_id
            $itemOwnerId = $likeable->user_id;
        }

        if (!$itemOwnerId) {
            Log::info('[NotificationSystem] SendNewLikeNotificationListener: Could not determine owner of the liked item.', ['likeable_id' => $likeable->id, 'likeable_type' => get_class($likeable)]);
            return;
        }


        if ($liker && $itemOwnerId == $liker->id) {
            return; // User liked their own item, no notification needed
        }

        if ($liker && $itemOwnerId) {
            $this->notificationCreator->createNotification(
                $itemOwnerId, // Recipient ID (owner of the liked item)
                'new_like',   // Notification type key (defined in config)
                [
                    'liker_id' => $liker->id,
                    'liker_name' => $liker->name, // Or a display name attribute
                    'likeable_id' => $likeable->id,
                    'likeable_type' => array_search(get_class($likeable), \Illuminate\Database\Eloquent\Relations\Relation::morphMap()) ?: get_class($likeable), // Morph alias or class name
                    'likeable_summary' => method_exists($likeable, 'getSummaryForNotification') ? $likeable->getSummaryForNotification() : Str::limit(optional($likeable)->content ?: optional($likeable)->title ?: 'item', 50), // Example summary
                ]
            );
        }
    }
}

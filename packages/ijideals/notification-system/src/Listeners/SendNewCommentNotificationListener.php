<?php

namespace Ijideals\NotificationSystem\Listeners;

use Ijideals\NotificationSystem\Services\NotificationCreationService;
use Illuminate\Contracts\Queue\ShouldQueue; // Optional
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Assume an event like this exists:
// namespace Ijideals\Commentable\Events;
// class CommentPosted { public $comment; public function __construct($comment) { $this->comment = $comment; } }

class SendNewCommentNotificationListener // implements ShouldQueue // Optional
{
    protected NotificationCreationService $notificationCreator;

    public function __construct(NotificationCreationService $notificationCreator)
    {
        $this->notificationCreator = $notificationCreator;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event Example: \Ijideals\Commentable\Events\CommentPosted
     * @return void
     */
    public function handle($event)
    {
        if (!isset($event->comment) ||
            !property_exists($event->comment, 'commentable') ||
            !property_exists($event->comment, 'commenter')) {
            Log::warning('[NotificationSystem] SendNewCommentNotificationListener: Event structure invalid.', ['event_class' => get_class($event)]);
            return;
        }

        $comment = $event->comment;
        $commentable = $comment->commentable; // Model commented on (e.g., Post)
        $commenter = $comment->commenter;   // User who posted the comment

        // 1. Notify owner of the commentable item (e.g., post author)
        $itemOwnerId = null;
        if (property_exists($commentable, 'author_id') && $commentable->author_id) {
            $itemOwnerId = $commentable->author_id;
        } elseif (property_exists($commentable, 'user_id') && $commentable->user_id) {
            $itemOwnerId = $commentable->user_id;
        }

        if (!$itemOwnerId) {
            Log::info('[NotificationSystem] SendNewCommentNotificationListener: Could not determine owner of the commented item.', ['commentable_id' => $commentable->id, 'commentable_type' => get_class($commentable)]);
            // No return here, as we might still notify parent comment author
        }


        if ($commenter && $itemOwnerId && $itemOwnerId != $commenter->id) {
            $this->notificationCreator->createNotification(
                $itemOwnerId,
                'new_comment',
                [
                    'commenter_id' => $commenter->id,
                    'commenter_name' => $commenter->name,
                    'comment_id' => $comment->id,
                    'comment_excerpt' => Str::limit($comment->content, 100),
                    'commentable_id' => $commentable->id,
                    'commentable_type' => array_search(get_class($commentable), \Illuminate\Database\Eloquent\Relations\Relation::morphMap()) ?: get_class($commentable),
                    'commentable_summary' => method_exists($commentable, 'getSummaryForNotification') ? $commentable->getSummaryForNotification() : Str::limit(optional($commentable)->content ?: optional($commentable)->title ?: 'item', 50),
                ]
            );
        }

        // 2. Notify author of the parent comment if this is a reply
        if ($comment->parent_id && property_exists($comment, 'parent') && $comment->parent && $comment->parent->commenter) {
            $parentCommentAuthor = $comment->parent->commenter;
            // Don't notify if the replier is the author of the parent comment OR if the replier is the owner of the main post (already notified above)
            if ($commenter && $parentCommentAuthor && $parentCommentAuthor->id != $commenter->id && $parentCommentAuthor->id != $itemOwnerId) {
                $this->notificationCreator->createNotification(
                    $parentCommentAuthor->id,
                    'new_reply', // Different notification type for replies
                    [
                        'replier_id' => $commenter->id,
                        'replier_name' => $commenter->name,
                        'reply_id' => $comment->id,
                        'reply_excerpt' => Str::limit($comment->content, 100),
                        'parent_comment_id' => $comment->parent_id,
                        'parent_comment_excerpt' => Str::limit($comment->parent->content, 50),
                        'commentable_id' => $commentable->id,
                        'commentable_type' => array_search(get_class($commentable), \Illuminate\Database\Eloquent\Relations\Relation::morphMap()) ?: get_class($commentable),
                        'commentable_summary' => method_exists($commentable, 'getSummaryForNotification') ? $commentable->getSummaryForNotification() : Str::limit(optional($commentable)->content ?: optional($commentable)->title ?: 'item', 50),
                    ]
                );
            }
        }
    }
}

<?php

namespace Ijideals\NotificationSystem\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection; // Eloquent Collection, not Illuminate\Support\Collection

trait HasNotifications
{
    /**
     * Get all notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications(): HasMany
    {
        $notificationModelClass = config('notification-system.notification_model', \Ijideals\NotificationSystem\Models\Notification::class);
        return $this->hasMany($notificationModelClass, 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get only the unread notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get only the read notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function readNotifications(): HasMany
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Get the count of unread notifications.
     * This can be accessed via $user->unread_notifications_count
     *
     * @return int
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        // Check if the count is already loaded via withCount('unreadNotifications')
        if (array_key_exists('unread_notifications_count', $this->attributes)) {
            return (int) $this->attributes['unread_notifications_count'];
        }
        return $this->unreadNotifications()->count();
    }

    /**
     * Mark all unread notifications for the user as read.
     *
     * @return bool Returns true if any notifications were updated.
     */
    public function markAllNotificationsAsRead(): bool
    {
        return $this->unreadNotifications()->update(['read_at' => now()]) > 0;
    }

    /**
     * Delete all notifications for the user.
     *
     * @param bool $onlyRead Delete only read notifications, or all?
     * @return int|false Number of notifications deleted, or false on error.
     */
    public function clearNotifications(bool $onlyRead = false)
    {
        $query = $this->notifications();
        if ($onlyRead) {
            $query->whereNotNull('read_at');
        }
        return $query->delete();
    }
}

<?php

namespace Ijideals\NotificationSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: if you plan to create a factory

class Notification extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Important for UUID primary keys if not incrementing
    public $incrementing = false;
    protected $keyType = 'string';


    protected $casts = [
        'data' => 'array', // For contextual information (e.g., post_id, liker_id)
        'read_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('notification-system.table_name', 'notifications');
    }

    /**
     * The user who this notification is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('notification-system.user_model'), 'user_id');
    }

    /**
     * Mark the notification as read.
     *
     * @return bool
     */
    public function markAsRead(): bool
    {
        if (is_null($this->read_at)) {
            return $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
        return true; // Already read
    }

    /**
     * Mark the notification as unread.
     *
     * @return bool
     */
    public function markAsUnread(): bool
    {
        if (!is_null($this->read_at)) {
            return $this->forceFill(['read_at' => null])->save();
        }
        return true; // Already unread
    }

    /**
     * Determine if a notification has been read.
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Determine if a notification is unread.
     *
     * @return bool
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // Optional: If you create a factory for this model
    protected static function newFactory()
    {
       return \Ijideals\NotificationSystem\Database\Factories\NotificationFactory::new();
    }
}

<?php

namespace Ijideals\NotificationSystem\Services;

use Ijideals\NotificationSystem\Models\Notification; // Default Notification model
use Illuminate\Support\Facades\Log; // Optional for logging

class NotificationCreationService
{
    protected $notificationModelClass;

    public function __construct()
    {
        $this->notificationModelClass = config('notification-system.notification_model', Notification::class);
    }

    /**
     * Create a new notification.
     *
     * @param int|string $userId The ID of the user to notify.
     * @param string $type The type of notification (e.g., 'new_like', 'new_comment').
     * @param array $data An array of contextual data for the notification.
     * @param bool $sendNow Placeholder for future real-time broadcasting (e.g., via websockets).
     * @return \Illuminate\Database\Eloquent\Model|null The created Notification model instance or null on failure.
     */
    public function createNotification(
        int|string $userId,
        string $type,
        array $data = [],
        bool $sendNow = false // For future real-time integration
    ): ?Model {
        if (empty($userId) || empty($type)) {
            Log::warning('[NotificationSystem] Attempted to create notification with missing user ID or type.', [
                'user_id' => $userId,
                'type' => $type,
            ]);
            return null;
        }

        // Integration with UserSettings: Check if user wants this type of DB notification
        $userModelClass = config('notification-system.user_model', \App\Models\User::class);
        $user = $userModelClass::find($userId);

        if ($user && method_exists($user, 'getSetting')) {
            // Construct the setting key, e.g., 'notifications.new_like.database'
            $settingKey = "notifications.{$type}.database";
            // Default to true if setting not found or UserSettings package isn't fully integrated with User model yet
            $isNotificationEnabled = $user->getSetting($settingKey, true);

            if (!$isNotificationEnabled) {
                Log::info("[NotificationSystem] Notification of type '{$type}' for user {$userId} skipped due to user preference.", ['setting_key' => $settingKey]);
                return null; // Do not create the notification
            }
        } else {
            // Log if user model or getSetting method is not available, but proceed for now.
            Log::warning("[NotificationSystem] User model or getSetting method not available for user {$userId}. Proceeding with notification creation.", [
                'user_model_exists' => class_exists($userModelClass),
                'method_exists_on_user' => $user ? method_exists($user, 'getSetting') : false,
            ]);
        }

        try {
            $notification = $this->notificationModelClass::create([
                'user_id' => $userId,
                'type' => $type,
                'data' => $data, // Automatically cast to JSON by Eloquent if $casts['data'] = 'array'
                // 'read_at' defaults to null
            ]);

            // Placeholder for real-time event broadcasting
            if ($sendNow) {
                // Example: event(new RealtimeNotificationCreated($notification));
                // This would require defining such an event and a broadcasting setup.
            }

            Log::info("[NotificationSystem] Notification created for user {$userId}, type '{$type}'.", ['data' => $data]);
            return $notification;

        } catch (\Exception $e) {
            Log::error('[NotificationSystem] Failed to create notification.', [
                'user_id' => $userId,
                'type' => $type,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

<?php

namespace Ijideals\NotificationSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Ijideals\NotificationSystem\Models\Notification; // Default Notification model

class NotificationController extends Controller
{
    protected $notificationModelClass;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->notificationModelClass = config('notification-system.notification_model', Notification::class);
    }

    /**
     * Display a listing of the authenticated user's notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = config('notification-system.pagination_items', 20);

        $query = $user->notifications(); // Uses the HasNotifications trait method

        if ($request->has('status')) {
            if ($request->status === 'read') {
                $query->read();
            } elseif ($request->status === 'unread') {
                $query->unread();
            }
        }

        // Eager load any related data if necessary from the 'data' column
        // For example, if 'data' contains actor_id and you want to load the actor User model.
        // This would require a custom presenter or resource class.
        // For now, we return the raw notification data.

        $notifications = $query->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param string $notificationId UUID of the notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(string $notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return response()->json(['message' => __('notification-system::notification-system.notification_not_found')], 404);
        }

        if ($notification->markAsRead()) {
            return response()->json(['message' => __('notification-system::notification-system.marked_as_read_success')]);
        }
        return response()->json(['message' => __('notification-system::notification-system.marked_as_read_failed')], 500);
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        if ($user->markAllNotificationsAsRead()) {
            return response()->json(['message' => __('notification-system::notification-system.all_marked_as_read_success')]);
        }
        return response()->json(['message' => __('notification-system::notification-system.all_marked_as_read_failed')], 200); // Or 204 No Content
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        $user = Auth::user();
        // The accessor from HasNotifications trait handles this efficiently
        return response()->json(['unread_count' => $user->unread_notifications_count]);
    }


    /**
     * Delete a specific notification.
     *
     * @param string $notificationId UUID of the notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return response()->json(['message' => __('notification-system::notification-system.notification_not_found')], 404);
        }

        if ($notification->delete()) {
            return response()->json(['message' => __('notification-system::notification-system.deleted_success')]);
        }
        return response()->json(['message' => __('notification-system::notification-system.deleted_failed')], 500);
    }

    /**
     * Delete all notifications for the authenticated user.
     * Can optionally delete only read notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearAll(Request $request)
    {
        $user = Auth::user();
        $onlyRead = $request->input('only_read', false);

        $count = $user->clearNotifications((bool) $onlyRead);

        if ($count !== false) {
            return response()->json(['message' => __('notification-system::notification-system.cleared_success', ['count' => $count])]);
        }
        return response()->json(['message' => __('notification-system::notification-system.clear_failed')], 500);
    }
}

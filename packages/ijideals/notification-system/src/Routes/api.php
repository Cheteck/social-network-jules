<?php

use Illuminate\Support\Facades\Route;
use Ijideals\NotificationSystem\Http\Controllers\NotificationController;

Route::prefix(config('notification-system.route_prefix', 'api/v1/notifications'))
    ->middleware(['api', 'auth:api']) // All routes require authentication
    ->name('notifications.') // Route name prefix
    ->group(function () {

        // Get all notifications for the authenticated user (paginated)
        // GET /prefix/
        // Optional query param: ?status=read or ?status=unread
        Route::get('/', [NotificationController::class, 'index'])->name('index');

        // Get the count of unread notifications
        // GET /prefix/unread-count
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread.count');

        // Mark a specific notification as read
        // PATCH /prefix/{notificationId}/read
        Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead'])
            ->name('markAsRead')
            ->where('notificationId', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}'); // UUID validation

        // Mark all unread notifications as read
        // POST /prefix/mark-all-as-read
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');

        // Delete a specific notification
        // DELETE /prefix/{notificationId}
        Route::delete('/{notificationId}', [NotificationController::class, 'destroy'])
            ->name('destroy')
            ->where('notificationId', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');

        // Delete all notifications for the user
        // DELETE /prefix/clear-all
        // Optional query param: ?only_read=true
        Route::delete('/clear-all', [NotificationController::class, 'clearAll'])->name('clearAll');

    });

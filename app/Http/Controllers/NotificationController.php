<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Fetch paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);

        if ($page === 1) {
            $request->user()->updateQuietly([
                'notifications_last_seen_at' => now(),
            ]);
        }

        $paginator = $request->user()
            ->notifications()
            ->latest()
            ->simplePaginate(10);

        $notifications = collect($paginator->items())->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->toISOString(),
                'created_at_human' => $notification->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'notifications' => $notifications,
            'has_more' => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Clear (delete) all notifications for the user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}

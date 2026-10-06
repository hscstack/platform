<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user && (! $user->last_active_at || $user->last_active_at->diffInMinutes(now()) >= 5)) {
            $user->updateQuietly(['last_active_at' => now()]);
            $user->last_active_at = now();
        }

        if ($user && ($request->is('chat*') || str_contains((string) $request->header('referer', ''), '/chat'))) {
            Cache::forever("user:{$user->id}:chat_last_seen_at", now()->timestamp);
        }

        if ($user && ($request->is('forum*') || str_contains((string) $request->header('referer', ''), '/forum'))) {
            Cache::forever("user:{$user->id}:forum_last_seen_at", now()->timestamp);
        }

        $latestChatMessage = Cache::get('chat:latest_message');

        $hasUnreadChat = false;
        if ($user && ! $request->is('chat*') && $latestChatMessage) {
            $chatLastSeen = Cache::get("user:{$user->id}:chat_last_seen_at");
            $isFromOtherUser = (int) $latestChatMessage['user_id'] !== (int) $user->id;
            $isNewer = ! $chatLastSeen || $latestChatMessage['created_at'] > $chatLastSeen;

            $hasUnreadChat = $isFromOtherUser && $isNewer;
        }

        $latestForumPost = Cache::get('forum:latest_post');

        $hasUnreadForum = false;
        if ($user && ! $request->is('forum*') && $latestForumPost) {
            $forumLastSeen = Cache::get("user:{$user->id}:forum_last_seen_at");
            $isFromOtherUser = (int) $latestForumPost['user_id'] !== (int) $user->id;
            $isNewer = ! $forumLastSeen || $latestForumPost['created_at'] > $forumLastSeen;

            $hasUnreadForum = $isFromOtherUser && $isNewer;
        }

        $canAccessAdmin = $user && Cache::remember(
            "user:{$user->id}:can_admin",
            now()->addDay(),
            fn () => $user->can('view admin')
        );

        $permissions = ($user && $canAccessAdmin)
            ? Cache::remember(
                "user:{$user->id}:permissions",
                now()->addDay(),
                fn () => $user->getAllPermissions()->pluck('name')->toArray()
            )
            : [];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'app_version' => config('app.version'),
            'auth' => [
                'user' => $user,
                'has_unread_notifications' => $user
                    ? (bool) Cache::rememberForever("user:{$user->id}:has_unread_notifs", fn () => $user->unreadNotifications()->exists())
                    : false,
                'has_unread_chat' => $hasUnreadChat,
                'has_unread_forum' => $hasUnreadForum,
                'can_access_admin' => (bool) $canAccessAdmin,
                'permissions' => $permissions,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

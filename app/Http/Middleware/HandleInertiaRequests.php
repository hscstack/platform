<?php

namespace App\Http\Middleware;

use App\Models\ChatMessage;
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
            $user->updateQuietly(['chat_last_seen_at' => now()]);
            $user->chat_last_seen_at = now();
        }

        $latestChatMessage = Cache::rememberForever('chat:latest_message', function () {
            $latest = ChatMessage::latest('id')->first(['id', 'user_id', 'created_at']);

            return $latest ? [
                'created_at' => $latest->created_at?->getTimestamp() ?? now()->getTimestamp(),
                'user_id' => $latest->user_id,
            ] : null;
        });

        $hasUnreadChat = false;
        if ($user && ! $request->is('chat*') && $latestChatMessage) {
            $isFromOtherUser = (int) $latestChatMessage['user_id'] !== (int) $user->id;
            $isNewer = ! $user->chat_last_seen_at || $latestChatMessage['created_at'] > $user->chat_last_seen_at->getTimestamp();

            $hasUnreadChat = $isFromOtherUser && $isNewer;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'app_version' => config('app.version'),
            'auth' => [
                'user' => $user,
                'unread_notifications_count' => $request->user()?->unreadNotifications()->when($request->user()?->notifications_last_seen_at, fn ($q, $seen) => $q->where('created_at', '>', $seen))->take(10)->count() ?? 0,
                'has_unread_chat' => $hasUnreadChat,
                'unread_chat_messages_count' => $hasUnreadChat ? 1 : 0,
                'can_access_admin' => $request->user()?->can('view admin') ?? false,
                'permissions' => $request->user()?->getAllPermissions()->pluck('name')->toArray() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\ChatMessage;
use App\Models\ForumPost;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        if ($user && ($request->is('forum*') || str_contains((string) $request->header('referer', ''), '/forum'))) {
            if (! $user->forum_last_seen_at || $user->forum_last_seen_at->diffInMinutes(now()) >= 1) {
                $user->updateQuietly(['forum_last_seen_at' => now()]);
                $user->forum_last_seen_at = now();
            }
        }

        $latestForumPostAt = Cache::remember('forum_latest_post_at', 300, function () {
            return ForumPost::approved()->latest('created_at')->value('created_at');
        });

        $hasUnreadForum = false;
        if ($user && ! $request->is('forum*')) {
            if ($latestForumPostAt) {
                $latestCarbon = $latestForumPostAt instanceof CarbonInterface
                    ? $latestForumPostAt
                    : Carbon::parse($latestForumPostAt);

                $hasUnreadForum = ! $user->forum_last_seen_at || $latestCarbon->gt($user->forum_last_seen_at);
            }
        }

        $permissionsData = $user
            ? Cache::remember("user_{$user->id}_permissions", now()->addDay(), function () use ($user) {
                return [
                    'can_access_admin' => (bool) $user->can('view admin'),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ];
            })
            : ['can_access_admin' => false, 'permissions' => []];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'app_version' => config('app.version'),
            'auth' => [
                'user' => $user,
                'unread_notifications_count' => $request->user()?->unreadNotifications()->when($request->user()?->notifications_last_seen_at, fn ($q, $seen) => $q->where('created_at', '>', $seen))->take(10)->count() ?? 0,
                'unread_chat_messages_count' => ($request->user() && ! $request->is('chat*'))
                    ? ChatMessage::where('user_id', '!=', $request->user()->id)
                        ->when(
                            $request->user()->chat_last_seen_at,
                            fn ($q, $seen) => $q->where('created_at', '>', $seen)
                        )->take(10)->count()
                    : 0,
                'has_unread_forum' => $hasUnreadForum,
                'can_access_admin' => $permissionsData['can_access_admin'],
                'permissions' => $permissionsData['permissions'],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

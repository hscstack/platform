<?php

namespace App\Http\Middleware;

use App\Models\Blog;
use App\Models\ChatMessage;
use App\Models\ForumPost;
use App\Models\User;
use Closure;
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
                'has_unread_chat' => $this->resolveUnreadStatus(
                    $request, $user, 'chat', 'chat:latest_message',
                    fn () => ChatMessage::latest('created_at')->first()
                ),
                'has_unread_forum' => $this->resolveUnreadStatus(
                    $request, $user, 'forum', 'forum:latest_post',
                    fn () => ForumPost::where('moderation_status', 'approved')->latest('created_at')->first()
                ),
                'has_unread_blogs' => $this->resolveUnreadStatus(
                    $request, $user, 'blogs', 'blogs:latest_post',
                    fn () => Blog::where('is_published', true)->latest('created_at')->first()
                ),
                'can_access_admin' => (bool) $canAccessAdmin,
                'permissions' => $permissions,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Resolves the unread red dot status for a given section with zero-write churn.
     */
    private function resolveUnreadStatus(Request $request, ?User $user, string $section, string $cacheKey, Closure $fallback): bool
    {
        if (! $user) {
            return false;
        }

        $latest = Cache::rememberForever($cacheKey, function () use ($fallback) {
            $item = $fallback();

            return $item ? [
                'user_id' => $item->user_id,
                'created_at' => $item->created_at?->getTimestamp() ?? now()->timestamp,
            ] : null;
        });

        if (! $latest) {
            return false;
        }

        $isFromOtherUser = (int) $latest['user_id'] !== (int) $user->id;
        $lastSeen = $user->lastSeen($section);
        $isNewer = ! $lastSeen || $latest['created_at'] > $lastSeen;

        if ($isFromOtherUser && $isNewer) {
            if ($request->is("{$section}*") || ($section === 'chat' && str_contains((string) $request->header('referer', ''), '/chat'))) {
                $user->markSeen($section);

                return false;
            }

            return true;
        }

        return false;
    }
}

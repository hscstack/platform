<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class BlogReactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Blog $blog,
        public User $reactor,
        public int $reactionsCount,
    ) {}

    /**
     * Send or roll up a blog reaction notification to the blog author.
     */
    public static function sendTo(User $targetUser, Blog $blog, User $reactor, int $reactionsCount): void
    {
        if ($targetUser->id === $reactor->id) {
            return;
        }

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['blog_id'] ?? null) === $blog->id);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $data['count'] = $count;
            $data['title'] = "{$reactor->name} and {$othersText} loved your blog post";
            $data['message'] = $blog->title;
            $data['reactions_count'] = $reactionsCount;

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);

            Cache::forever("user:{$targetUser->id}:has_unread_notifs", true);
        } else {
            $targetUser->notify(new self($blog, $reactor, $reactionsCount));
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'blog_reaction',
            'title' => "{$this->reactor->name} loved your blog post",
            'message' => $this->blog->title,
            'url' => route('blogs.show', $this->blog->slug),
            'blog_id' => $this->blog->id,
            'reactor_id' => $this->reactor->id,
            'reactions_count' => $this->reactionsCount,
            'count' => 1,
        ];
    }
}

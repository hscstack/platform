<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class BlogCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Blog $blog,
        public BlogComment $comment,
    ) {}

    /**
     * Send or roll up a blog comment notification to the blog author.
     */
    public static function sendTo(User $targetUser, Blog $blog, BlogComment $comment): void
    {
        if ($targetUser->id === $comment->user_id) {
            return;
        }

        $blogId = $blog->id;

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['blog_id'] ?? null) === $blogId);

        $commenterName = $comment->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($comment->content), 80);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $data['count'] = $count;
            $data['title'] = "{$commenterName} and {$othersText} commented on your blog";
            $data['message'] = "{$commenterName}: \"{$snippet}\"";
            $data['comment_id'] = $comment->id;

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);
        } else {
            $targetUser->notify(new self($blog, $comment));
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $commenterName = $this->comment->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($this->comment->content), 80);

        return [
            'type' => 'blog_comment',
            'title' => "New comment on \"{$this->blog->title}\"",
            'message' => "{$commenterName}: \"{$snippet}\"",
            'url' => route('blogs.show', $this->blog->slug).'#comments',
            'blog_id' => $this->blog->id,
            'comment_id' => $this->comment->id,
            'count' => 1,
        ];
    }
}

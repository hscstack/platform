<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class BlogCommentMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Blog $blog,
        public BlogComment $comment,
        public User $sender,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $snippet = Str::limit(strip_tags($this->comment->content), 80);

        return [
            'type' => 'user_mention',
            'title' => "@{$this->sender->username} mentioned you on \"{$this->blog->title}\"",
            'message' => "\"{$snippet}\"",
            'url' => route('blogs.show', $this->blog->slug).'#comments',
            'blog_id' => $this->blog->id,
            'comment_id' => $this->comment->id,
            'sender_id' => $this->sender->id,
        ];
    }
}

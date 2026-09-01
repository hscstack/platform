<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\BlogComment;
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

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'blog_comment',
            'title' => "New comment on \"{$this->blog->title}\"",
            'message' => "{$this->comment->user->name}: ".Str::limit($this->comment->content, 80),
            'url' => route('blogs.show', $this->blog->slug).'#comments',
        ];
    }
}

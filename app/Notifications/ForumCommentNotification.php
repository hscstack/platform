<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ForumCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public ForumAnswer $answer,
        public User $commenter
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $commentSnippet = Str::limit(strip_tags($this->answer->body), 80);

        return [
            'type' => 'forum_comment',
            'title' => "{$this->commenter->name} answered your question",
            'message' => "\"{$commentSnippet}\"",
            'url' => route('forum.show', $this->post->slug),
            'commenter_name' => $this->commenter->name,
            'commenter_username' => $this->commenter->username,
            'post_title' => $this->post->title,
        ];
    }
}

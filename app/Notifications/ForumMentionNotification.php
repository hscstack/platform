<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ForumMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public ForumAnswer $answer,
        public User $sender,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $snippet = Str::limit(strip_tags($this->answer->body), 80);
        $postSnippet = $this->post->snippet(40);

        return [
            'type' => 'user_mention',
            'title' => "@{$this->sender->username} mentioned you on \"{$postSnippet}\"",
            'message' => "\"{$snippet}\"",
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
            'answer_id' => $this->answer->id,
            'sender_id' => $this->sender->id,
        ];
    }
}

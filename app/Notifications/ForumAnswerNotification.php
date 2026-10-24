<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ForumAnswerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public ForumAnswer $answer,
        public bool $isReply = false,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $authorName = $this->answer->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($this->answer->body), 80);
        $postSnippet = $this->post->snippet(40);
        $title = $this->isReply
            ? "New reply to your answer on \"{$postSnippet}\""
            : "New answer to your question \"{$postSnippet}\"";

        return [
            'type' => 'forum_comment',
            'title' => $title,
            'message' => "{$authorName}: \"{$snippet}\"",
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
            'answer_id' => $this->answer->id,
        ];
    }
}

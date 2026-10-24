<?php

namespace App\Notifications;

use App\Models\ForumPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ForumStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public string $status,
        public ?string $actionTitle = null,
        public ?string $actionMessage = null,
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
        $statusLabels = [
            'approved' => 'Your question was approved',
            'flagged' => 'Your question was flagged for review',
            'rejected' => 'Your question was rejected by moderators',
            'locked' => 'Your question was locked',
            'unlocked' => 'Your question was unlocked',
        ];

        $postSnippet = Str::limit(strip_tags($this->post->body ?? ''), 40);
        $title = $this->actionTitle ?? ($statusLabels[$this->status] ?? 'Discussion update');
        $message = $this->actionMessage ?? "\"{$postSnippet}\"";

        return [
            'type' => 'forum_status',
            'status' => $this->status,
            'title' => $title,
            'message' => $message,
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
        ];
    }
}

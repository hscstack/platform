<?php

namespace App\Notifications;

use App\Models\ForumPost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ForumPostStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public string $status,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusTitles = [
            'approved' => 'Your question was approved!',
            'rejected' => 'Your question was rejected',
            'flagged' => 'Your question was flagged for review',
            'locked' => 'Your discussion was locked',
            'unlocked' => 'Your discussion was unlocked',
        ];

        $statusMessages = [
            'approved' => 'Your question is now published and visible to the HSC Stack community.',
            'rejected' => 'Your question was reviewed and declined by moderators.',
            'flagged' => 'Your question has been flagged for moderator review.',
            'locked' => 'Replies and new answers on your question have been disabled.',
            'unlocked' => 'Replies and new answers on your question have been re-enabled.',
        ];

        return [
            'type' => 'forum_status',
            'status' => $this->status,
            'title' => $statusTitles[$this->status] ?? "Question status: {$this->status}",
            'message' => $this->reason ?: ($statusMessages[$this->status] ?? $this->post->title),
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
        ];
    }
}

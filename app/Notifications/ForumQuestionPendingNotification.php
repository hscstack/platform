<?php

namespace App\Notifications;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ForumQuestionPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public User $author,
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
        return [
            'type' => 'forum_pending',
            'post_id' => $this->post->id,
            'title' => 'New Question Pending Review',
            'message' => "{$this->author->name}: \"{$this->post->title}\"",
            'url' => route('admin.forums.index', ['status' => 'pending']),
        ];
    }
}

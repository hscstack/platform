<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ForumVoteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost|ForumAnswer $item,
        public User $voter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isQuestion = $this->item instanceof ForumPost;
        $title = $isQuestion ? $this->item->title : $this->item->post->title;
        $slug = $isQuestion ? $this->item->slug : $this->item->post->slug;

        return [
            'type' => 'forum_vote',
            'title' => "{$this->voter->name} upvoted your ".($isQuestion ? 'question' : 'answer'),
            'message' => "\"{$title}\"",
            'url' => route('forum.show', $slug),
        ];
    }
}

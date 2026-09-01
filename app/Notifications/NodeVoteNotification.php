<?php

namespace App\Notifications;

use App\Models\Node;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NodeVoteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Node $node,
        public User $voter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $subject = $this->node->subject;
        $url = $subject ? url("/{$subject->slug}/{$this->node->slug}") : url('/');

        return [
            'type' => 'node_vote',
            'title' => "{$this->voter->name} upvoted your folder",
            'message' => "\"{$this->node->name}\"",
            'url' => $url,
            'node_id' => $this->node->id,
        ];
    }
}

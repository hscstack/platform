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
        if ($subject) {
            $path = implode('/', array_column($this->node->breadcrumb(), 'slug'));
            $url = url("/{$subject->slug}/{$path}");
        } else {
            $url = url('/');
        }

        return [
            'type' => 'node_vote',
            'title' => "{$this->voter->name} upvoted your folder",
            'message' => "\"{$this->node->name}\"",
            'url' => $url,
            'node_id' => $this->node->id,
        ];
    }
}

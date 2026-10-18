<?php

namespace App\Notifications;

use App\Models\Node;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class NodeVoteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Node $node,
        public User $voter,
    ) {}

    /**
     * Send or roll up a node vote notification to the node contributor.
     */
    public static function sendTo(User $targetUser, Node $node, User $voter): void
    {
        if ($targetUser->id === $voter->id) {
            return;
        }

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['node_id'] ?? null) === $node->id);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $data['count'] = $count;
            $data['title'] = "{$voter->name} and {$othersText} upvoted your folder";
            $data['message'] = "\"{$node->name}\"";

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);

            Cache::forever("user:{$targetUser->id}:has_unread_notifs", true);
        } else {
            $targetUser->notify(new self($node, $voter));
        }
    }

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
            'count' => 1,
        ];
    }
}

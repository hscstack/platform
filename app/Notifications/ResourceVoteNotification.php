<?php

namespace App\Notifications;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ResourceVoteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Resource $resource,
        public User $voter,
    ) {}

    /**
     * Send or roll up a resource vote notification to the author.
     */
    public static function sendTo(User $targetUser, Resource $resource, User $voter): void
    {
        if ($targetUser->id === $voter->id) {
            return;
        }

        $resourceId = $resource->id;

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['resource_id'] ?? null) === $resourceId);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $data['count'] = $count;
            $data['title'] = "{$voter->name} and {$othersText} upvoted your resource";
            $data['message'] = "\"{$resource->title}\"";

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);

            Cache::forever("user:{$targetUser->id}:has_unread_notifs", true);
        } else {
            $targetUser->notify(new self($resource, $voter));
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resource_vote',
            'title' => "{$this->voter->name} upvoted your resource",
            'message' => "\"{$this->resource->title}\"",
            'url' => "/resources/{$this->resource->id}",
            'resource_id' => $this->resource->id,
            'count' => 1,
        ];
    }
}

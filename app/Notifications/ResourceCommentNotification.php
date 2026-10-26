<?php

namespace App\Notifications;

use App\Models\Resource;
use App\Models\ResourceComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ResourceCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Resource $resource,
        public ResourceComment $comment,
    ) {}

    /**
     * Send or roll up a resource comment notification to the resource author.
     */
    public static function sendTo(User $targetUser, Resource $resource, ResourceComment $comment): void
    {
        if ($targetUser->id === $comment->user_id) {
            return;
        }

        $resourceId = $resource->id;

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['resource_id'] ?? null) === $resourceId);

        $commenterName = $comment->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($comment->content), 80);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $data['count'] = $count;
            $data['title'] = "{$commenterName} and {$othersText} commented on your resource";
            $data['message'] = "{$commenterName}: \"{$snippet}\"";
            $data['comment_id'] = $comment->id;

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);
        } else {
            $targetUser->notify(new self($resource, $comment));
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $commenterName = $this->comment->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($this->comment->content), 80);

        return [
            'type' => 'resource_comment',
            'title' => "New comment on \"{$this->resource->title}\"",
            'message' => "{$commenterName}: \"{$snippet}\"",
            'url' => route('resources.show', $this->resource->id).'#comments',
            'resource_id' => $this->resource->id,
            'comment_id' => $this->comment->id,
            'count' => 1,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Resource;
use App\Models\ResourceComment;
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

    public function via(object $notifiable): array
    {
        // Deliver resource comment notification via database
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Deep-link notification click directly to #comments section on resource page
        return [
            'type' => 'resource_comment',
            'title' => "New comment on \"{$this->resource->title}\"",
            'message' => "{$this->comment->user->name}: ".Str::limit($this->comment->content, 80),
            'url' => route('resources.show', $this->resource->id).'#comments',
            'resource_id' => $this->resource->id,
            'comment_id' => $this->comment->id,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Resource;
use App\Models\ResourceComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ResourceCommentMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Resource $resource,
        public ResourceComment $comment,
        public User $sender,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $snippet = Str::limit(strip_tags($this->comment->content), 80);

        return [
            'type' => 'user_mention',
            'title' => "@{$this->sender->username} mentioned you on \"{$this->resource->title}\"",
            'message' => "\"{$snippet}\"",
            'url' => route('resources.show', $this->resource->id).'#comments',
            'resource_id' => $this->resource->id,
            'comment_id' => $this->comment->id,
            'sender_id' => $this->sender->id,
        ];
    }
}

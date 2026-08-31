<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserMentionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $mentioner,
        public string $sourceTitle,
        public string $body,
        public string $url
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $snippet = Str::limit(strip_tags($this->body), 80);

        return [
            'type' => 'user_mention',
            'title' => "{$this->mentioner->name} mentioned you",
            'message' => "\"{$snippet}\"",
            'url' => $this->url,
            'mentioner_name' => $this->mentioner->name,
            'mentioner_username' => $this->mentioner->username,
            'source_title' => $this->sourceTitle,
        ];
    }
}

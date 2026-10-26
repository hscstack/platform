<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $sender,
        public string $contextTitle,
        public string $content,
        public string $url,
        public ?string $itemType = null,
        public ?int $itemId = null,
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
        $snippet = Str::limit(strip_tags($this->content), 80);

        return [
            'type' => 'user_mention',
            'title' => "@{$this->sender->username} mentioned you in {$this->contextTitle}",
            'message' => "\"{$snippet}\"",
            'url' => $this->url,
            'sender_id' => $this->sender->id,
            'item_type' => $this->itemType,
            'item_id' => $this->itemId,
        ];
    }
}

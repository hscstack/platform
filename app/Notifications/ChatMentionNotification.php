<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ChatMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChatMessage $chatMessage,
        public User $sender,
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
        $snippet = Str::limit(strip_tags($this->chatMessage->content), 80);

        return [
            'type' => 'user_mention',
            'title' => "@{$this->sender->username} mentioned you in Global Chat",
            'message' => "\"{$snippet}\"",
            'url' => route('chat.index'),
            'message_id' => $this->chatMessage->id,
            'sender_id' => $this->sender->id,
        ];
    }
}

<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ChatMessage $chatMessage)
    {
        $chatMessage->loadMissing(['user:id,name,username,image_path,institution', 'user.roles:id,name']);

        $this->message = [
            'id' => $chatMessage->id,
            'content' => $chatMessage->deleted_at ? 'This message was deleted by a moderator.' : $chatMessage->content,
            'is_deleted' => $chatMessage->deleted_at !== null,
            'deleted_at' => $chatMessage->deleted_at?->toIso8601String(),
            'reply_to_id' => $chatMessage->reply_to_id,
            'reply_to_content' => $chatMessage->reply_to_content,
            'created_at' => $chatMessage->created_at->toIso8601String(),
            'user' => [
                'id' => $chatMessage->user->id,
                'name' => $chatMessage->user->name,
                'username' => $chatMessage->user->username,
                'image_url' => $chatMessage->user->image_url,
                'institution' => $chatMessage->user->institution,
                'is_verified' => $chatMessage->user->is_verified,
                'roles' => $chatMessage->user->roles->pluck('name')->toArray(),
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('global-chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}

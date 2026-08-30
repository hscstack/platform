<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;

    public string $deletedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(int $messageId, ?string $deletedAt = null)
    {
        $this->messageId = $messageId;
        $this->deletedAt = $deletedAt ?? now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channel = app()->environment('production') ? 'global-chat' : app()->environment().'.global-chat';

        return [
            new Channel($channel),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    /**
     * Data to broadcast with the event.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'messageId' => $this->messageId,
            'deleted_at' => $this->deletedAt,
        ];
    }
}

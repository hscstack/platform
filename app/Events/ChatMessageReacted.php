<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageReacted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;

    public array $reactions;

    /**
     * Create a new event instance.
     */
    public function __construct(int $messageId, array $reactions)
    {
        $this->messageId = $messageId;
        $this->reactions = $reactions;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        $channel = app()->environment('production') ? 'global-chat' : app()->environment().'.global-chat';

        return [
            new PresenceChannel($channel),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.reacted';
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
            'reactions' => $this->reactions,
        ];
    }
}

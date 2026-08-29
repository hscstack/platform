<?php

namespace App\Events;

use App\Models\AppSetting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatSettingsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $settings;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->settings = [
            'enabled' => (bool) AppSetting::get('global_chat_enabled', true),
            'audience' => AppSetting::get('global_chat_audience', 'verified_members'),
            'disabled_reason' => (string) AppSetting::get('global_chat_disabled_reason', ''),
            'cooldown_seconds' => (int) AppSetting::get('global_chat_cooldown_seconds', 30),
            'max_messages' => (int) AppSetting::get('global_chat_max_messages', 200),
            'max_length' => (int) AppSetting::get('global_chat_max_length', 280),
            'allowed_emojis' => (array) AppSetting::get('global_chat_allowed_emojis', ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏']),
        ];
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
        return 'settings.updated';
    }
}

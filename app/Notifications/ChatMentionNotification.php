<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMentionNotification extends UserMentionNotification
{
    public function __construct(
        public ChatMessage $chatMessage,
        public User $sender,
    ) {
        parent::__construct(
            sender: $sender,
            contextTitle: 'Global Chat',
            content: $chatMessage->content,
            url: route('chat.index'),
            itemType: 'chat_message',
            itemId: $chatMessage->id,
        );
    }
}

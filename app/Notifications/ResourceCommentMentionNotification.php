<?php

namespace App\Notifications;

use App\Models\Resource;
use App\Models\ResourceComment;
use App\Models\User;

class ResourceCommentMentionNotification extends UserMentionNotification
{
    public function __construct(
        public Resource $resource,
        public ResourceComment $comment,
        public User $sender,
    ) {
        $context = $resource ? "\"{$resource->title}\"" : 'Resource';

        parent::__construct(
            sender: $sender,
            contextTitle: $context,
            content: $comment->content,
            url: route('resources.show', $resource->id).'#comments',
            itemType: 'resource',
            itemId: $resource->id,
        );
    }
}

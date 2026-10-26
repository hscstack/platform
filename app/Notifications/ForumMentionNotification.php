<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;

class ForumMentionNotification extends UserMentionNotification
{
    public function __construct(
        public ForumPost $post,
        public ForumAnswer $answer,
        public User $sender,
    ) {
        $context = $post ? "discussion \"{$post->snippet(40)}\"" : 'Forum';

        parent::__construct(
            sender: $sender,
            contextTitle: $context,
            content: $answer->body,
            url: route('forum.show', $post->slug),
            itemType: 'forum_post',
            itemId: $post->id,
        );
    }
}

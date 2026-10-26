<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\User;

class BlogCommentMentionNotification extends UserMentionNotification
{
    public function __construct(
        public Blog $blog,
        public BlogComment $comment,
        public User $sender,
    ) {
        $context = $blog ? "\"{$blog->title}\"" : 'Blog';

        parent::__construct(
            sender: $sender,
            contextTitle: $context,
            content: $comment->content,
            url: route('blogs.show', $blog->slug).'#comments',
            itemType: 'blog',
            itemId: $blog->id,
        );
    }
}

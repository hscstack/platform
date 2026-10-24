<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ForumVoteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost|ForumAnswer $item,
        public User $voter,
    ) {}

    /**
     * Send or roll up a forum vote notification to the author.
     */
    public static function sendTo(User $targetUser, ForumPost|ForumAnswer $item, User $voter): void
    {
        if ($targetUser->id === $voter->id) {
            return;
        }

        $isQuestion = $item instanceof ForumPost;
        $targetType = $isQuestion ? 'question' : 'answer';
        $itemId = $item->id;
        $itemType = $isQuestion ? 'post' : 'answer';

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['item_type'] ?? null) === $itemType && ($n->data['item_id'] ?? null) === $itemId);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $post = $isQuestion ? $item : $item->post;
            $title = $post->snippet(40);

            $data['count'] = $count;
            $data['title'] = "{$voter->name} and {$othersText} upvoted your {$targetType}";
            $data['message'] = "\"{$title}\"";

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);
        } else {
            $targetUser->notify(new self($item, $voter));
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isQuestion = $this->item instanceof ForumPost;
        $post = $isQuestion ? $this->item : $this->item->post;
        $title = $post->snippet(40);
        $slug = $post->slug;

        return [
            'type' => 'forum_vote',
            'title' => "{$this->voter->name} upvoted your ".($isQuestion ? 'question' : 'answer'),
            'message' => "\"{$title}\"",
            'url' => route('forum.show', $slug),
            'item_type' => $isQuestion ? 'post' : 'answer',
            'item_id' => $this->item->id,
            'count' => 1,
        ];
    }
}

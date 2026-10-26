<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ForumAnswerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public ForumAnswer $answer,
        public bool $isReply = false,
    ) {}

    /**
     * Send or roll up a forum answer/reply notification to the recipient.
     */
    public static function sendTo(User $targetUser, ForumPost $post, ForumAnswer $answer, bool $isReply = false): void
    {
        if ($targetUser->id === $answer->user_id) {
            return;
        }

        $postId = $post->id;

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->get()
            ->first(fn ($n) => ($n->data['post_id'] ?? null) === $postId && ($n->data['is_reply'] ?? false) === $isReply);

        $authorName = $answer->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($answer->body), 80);
        $postSnippet = $post->snippet(40);

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $action = $isReply ? 'replied to your answer on' : 'answered your question';

            $data['count'] = $count;
            $data['title'] = "{$authorName} and {$othersText} {$action} \"{$postSnippet}\"";
            $data['message'] = "{$authorName}: \"{$snippet}\"";
            $data['answer_id'] = $answer->id;

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);
        } else {
            $targetUser->notify(new self($post, $answer, $isReply));
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $authorName = $this->answer->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($this->answer->body), 80);
        $postSnippet = $this->post->snippet(40);
        $title = $this->isReply
            ? "New reply to your answer on \"{$postSnippet}\""
            : "New answer to your question \"{$postSnippet}\"";

        return [
            'type' => 'forum_comment',
            'title' => $title,
            'message' => "{$authorName}: \"{$snippet}\"",
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
            'answer_id' => $this->answer->id,
            'is_reply' => $this->isReply,
            'count' => 1,
        ];
    }
}

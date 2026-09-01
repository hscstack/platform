<?php

namespace App\Notifications;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Only send email for direct answers to questions (not sub-replies), if user is opted in
        if (! $this->isReply && ! empty($notifiable->email) && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $authorName = $this->answer->user?->name ?? 'A student';
        $snippet = Str::limit(strip_tags($this->answer->body), 150);

        return (new MailMessage)
            ->subject("New answer to your question: \"{$this->post->title}\" 💬")
            ->view('emails.default', [
                'subject' => "New answer on \"{$this->post->title}\"",
                'greeting' => "Hello {$notifiable->name},",
                'lines' => [
                    "{$authorName} just answered your question \"{$this->post->title}\":",
                    "\"{$snippet}\"",
                ],
                'actionText' => 'View Answer',
                'actionUrl' => route('forum.show', $this->post->slug),
            ]);
    }

    /**
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $authorName = $this->answer->user?->name ?? 'Someone';
        $snippet = Str::limit(strip_tags($this->answer->body), 80);
        $title = $this->isReply
            ? "New reply to your answer on \"{$this->post->title}\""
            : "New answer to your question \"{$this->post->title}\"";

        return [
            'type' => 'forum_comment',
            'title' => $title,
            'message' => "{$authorName}: \"{$snippet}\"",
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
            'answer_id' => $this->answer->id,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\ForumPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForumStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ForumPost $post,
        public string $status,
        public ?string $actionTitle = null,
        public ?string $actionMessage = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Send email on approval, flagged, and rejected if user has emails enabled
        if (in_array($this->status, ['approved', 'flagged', 'rejected'], true)
            && ! empty($notifiable->email)
            && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->status) {
            'approved' => 'Your question was approved! 🎉',
            'flagged' => 'Important notice regarding your question ⚠️',
            'rejected' => 'Your question was rejected by moderators 🚫',
            default => "Update on your discussion: \"{$this->post->title}\"",
        };

        $lines = match ($this->status) {
            'approved' => [
                "Great news! Your question \"{$this->post->title}\" has been approved by our moderation team.",
                'It is now publicly visible in the forum for other students and mentors to answer.',
            ],
            'flagged' => [
                "Your question \"{$this->post->title}\" was flagged for review following community reports.",
                'It has been temporarily hidden while our moderation team reviews the content against our community guidelines.',
            ],
            'rejected' => [
                "Your question \"{$this->post->title}\" was reviewed and rejected by our moderation team.",
                'Please ensure all questions adhere to our content policy and community guidelines.',
            ],
            default => [
                "There has been an update on your discussion \"{$this->post->title}\".",
            ],
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.default', [
                'subject' => $subject,
                'greeting' => "Hello {$notifiable->name},",
                'lines' => $lines,
                'actionText' => $this->status === 'approved' ? 'View Question' : 'Visit HSCStack',
                'actionUrl' => $this->status === 'approved' ? route('forum.show', $this->post->slug) : route('index'),
            ]);
    }

    /**
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'approved' => 'Your question was approved',
            'flagged' => 'Your question was flagged for review',
            'rejected' => 'Your question was rejected by moderators',
            'locked' => 'Your question was locked',
            'unlocked' => 'Your question was unlocked',
        ];

        $title = $this->actionTitle ?? ($statusLabels[$this->status] ?? 'Discussion update');
        $message = $this->actionMessage ?? "\"{$this->post->title}\"";

        return [
            'type' => 'forum_status',
            'status' => $this->status,
            'title' => $title,
            'message' => $message,
            'url' => route('forum.show', $this->post->slug),
            'post_id' => $this->post->id,
        ];
    }
}

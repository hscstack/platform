<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BlogReactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Blog $blog,
        public User $reactor,
        public int $reactionsCount,
    ) {}

    public function isMilestone(): bool
    {
        $milestones = [1, 10, 25, 50, 100, 250, 500, 1000];

        return in_array($this->reactionsCount, $milestones, true)
            || ($this->reactionsCount > 1000 && $this->reactionsCount % 500 === 0);
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->isMilestone() && ! empty($notifiable->email) && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $headline = $this->reactionsCount === 1
            ? 'Your blog received its first love reaction! ❤️'
            : "🎉 Milestone: {$this->reactionsCount} people loved your blog post!";

        return (new MailMessage)
            ->subject($headline)
            ->view('emails.default', [
                'subject' => $headline,
                'greeting' => "Hello {$notifiable->name},",
                'lines' => [
                    "{$this->reactor->name} just loved your blog post \"{$this->blog->title}\".",
                    "Your article has reached a milestone of {$this->reactionsCount} love reactions!",
                    'Thank you for creating content that resonates with our community.',
                ],
                'actionText' => 'View Blog Post',
                'actionUrl' => route('blogs.show', $this->blog->slug),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'blog_reaction',
            'title' => "{$this->reactor->name} loved your blog post",
            'message' => $this->blog->title,
            'url' => route('blogs.show', $this->blog->slug),
        ];
    }
}

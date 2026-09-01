<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAppreciationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $appreciator,
        public int $totalAppreciations,
    ) {}

    /**
     * Determine if this count represents an achievement milestone.
     */
    public function isMilestone(): bool
    {
        $milestones = [1, 10, 25, 50, 100, 250, 500, 1000];

        return in_array($this->totalAppreciations, $milestones, true)
            || ($this->totalAppreciations > 1000 && $this->totalAppreciations % 500 === 0);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->isMilestone() && ! empty($notifiable->email) && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->totalAppreciations === 1
            ? '🎉 You received your first community appreciation!'
            : "⭐ Milestone: {$this->totalAppreciations} students appreciated your profile!";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.default', [
                'subject' => $subject,
                'greeting' => "Hello {$notifiable->name},",
                'lines' => [
                    "{$this->appreciator->name} (@{$this->appreciator->username}) just appreciated your contributions on HSCStack!",
                    "You have now received a total of {$this->totalAppreciations} appreciation".($this->totalAppreciations === 1 ? '' : 's').' from the community.',
                    'Thank you for your active participation and helpful presence.',
                ],
                'actionText' => 'View Profile',
                'actionUrl' => route('user.profile', $notifiable->username),
            ]);
    }

    /**
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_appreciation',
            'title' => "{$this->appreciator->name} appreciated you!",
            'message' => "You have {$this->totalAppreciations} community appreciations.",
            'url' => route('user.profile', $notifiable->username),
            'appreciator_id' => $this->appreciator->id,
            'total_appreciations' => $this->totalAppreciations,
        ];
    }
}

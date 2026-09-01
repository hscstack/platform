<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSuspensionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public bool $isBanned,
        public ?string $bannedUntilFormatted = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email) && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isBanned) {
            $subject = 'Account Suspension Notice ⚠️';
            $lines = [
                'Your HSCStack account has been temporarily suspended from community participation due to guideline violations.',
                "Suspension active until: {$this->bannedUntilFormatted}.",
                'During this period, posting in the forum and global chat will be restricted. Please review our community guidelines.',
            ];
            $actionText = 'Community Guidelines';
            $actionUrl = url('/content-policy');
        } else {
            $subject = 'Your Account Suspension Has Been Lifted 🎉';
            $lines = [
                'Great news! Your account suspension has been lifted by our moderation team.',
                'You can now participate in community discussions, forum questions, and global chat again.',
            ];
            $actionText = 'Visit HSCStack';
            $actionUrl = route('index');
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.default', [
                'subject' => $subject,
                'greeting' => "Hello {$notifiable->name},",
                'lines' => $lines,
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
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
            'type' => 'user_suspension',
            'is_banned' => $this->isBanned,
            'title' => $this->isBanned ? 'Account Suspended' : 'Suspension Lifted',
            'message' => $this->isBanned
                ? "Suspended until {$this->bannedUntilFormatted}."
                : 'Your community access has been restored.',
            'url' => route('index'),
        ];
    }
}

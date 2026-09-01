<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email) && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to HSCStack! 🎓')
            ->view('emails.default', [
                'subject' => 'Welcome to HSCStack! 🎓',
                'greeting' => "Hello {$notifiable->name},",
                'lines' => [
                    'Welcome to HSCStack! We are thrilled to have you in our learning community.',
                    'You can explore curated study notes, syllabus trees, participate in the community forum, and read blogs.',
                ],
                'actionText' => 'Get Started',
                'actionUrl' => route('me'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Welcome to HSCStack! 🎓',
            'message' => 'Your account is ready. Explore notes, syllabus, and discussions.',
            'url' => route('me'),
        ];
    }
}

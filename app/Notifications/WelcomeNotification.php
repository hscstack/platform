<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

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
        $appUrl = config('app.url', 'https://hscstack.site');
        $recipientName = htmlspecialchars($notifiable->name ?? 'Student', ENT_QUOTES, 'UTF-8');

        $mailContent = '<p>Welcome to <strong>HSCStack</strong>! We are thrilled to have you as part of our learning community.</p>'
            .'<p>Here is what you can do on HSCStack:</p>'
            .'<ul style="padding-left: 20px; margin: 16px 0; line-height: 1.8;">'
            .'<li><strong>Explore Syllabus & Notes:</strong> Access curated study notes, lecture PDFs, and video resources.</li>'
            .'<li><strong>Ask & Discuss:</strong> Ask questions and get answers in our interactive Community Q&A forum.</li>'
            .'<li><strong>Read Student Blogs:</strong> Share your knowledge and read articles from fellow students and mentors.</li>'
            .'<li><strong>Live Global Chat:</strong> Connect in real-time with fellow learners.</li>'
            .'</ul>'
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$appUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 11px 22px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 14px;\">"
            .'Get Started on HSCStack &rarr;'
            .'</a>'
            .'</p>';

        return (new MailMessage)
            ->subject('Welcome to HSCStack! 🎓')
            ->view('emails.bulk_announcement', [
                'mailSubject' => 'Welcome to HSCStack! 🎓',
                'mailContent' => $mailContent,
                'recipientName' => $recipientName,
                'imageUrl' => null,
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
            'type' => 'welcome',
            'title' => 'Welcome to HSCStack! 🎓',
            'message' => 'Your account is ready. Explore curated notes, syllabus, questions, and community discussions.',
            'url' => route('index'),
        ];
    }
}

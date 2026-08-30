<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAppreciationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailContent,
        public ?string $recipientName = null,
    ) {}

    public static function forMilestone(User $user, User $appreciator, int $milestoneCount): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $profileUrl = rtrim($appUrl, '/')."/u/{$user->username}";
        $appreciatorName = htmlspecialchars($appreciator->name, ENT_QUOTES, 'UTF-8');
        $recipientName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');

        if ($milestoneCount === 1) {
            $mailSubject = 'Someone appreciated your profile on HSCStack! ❤️';
            $headline = 'Congratulations! You received your first community appreciation ❤️';
            $message = "<p><strong>{$appreciatorName}</strong> just appreciated your profile and contributions on <a href=\"{$profileUrl}\" target=\"_blank\"><strong>HSCStack</strong></a>.</p>"
                .'<p>Your presence and contributions are helping fellow students in the community. Keep up the amazing work!</p>';
        } else {
            $mailSubject = "🎉 Milestone: {$milestoneCount} people have appreciated your profile!";
            $headline = "🎉 Exciting News! {$milestoneCount} Appreciations Milestone reached!";
            $message = "<p>Your profile on <a href=\"{$profileUrl}\" target=\"_blank\"><strong>HSCStack</strong></a> just reached <strong>{$milestoneCount} community appreciations</strong>, with the latest from <strong>{$appreciatorName}</strong>!</p>"
                .'<p>Thank you for inspiring and supporting students and contributors across the platform.</p>';
        }

        $mailContent = "<p style=\"font-size: 16px; font-weight: 700; color: #4f46e5;\">{$headline}</p>"
            .$message
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$profileUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View Your Profile &rarr;'
            .'</a>'
            .'</p>';

        return new self($mailSubject, $mailContent, $user->name);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bulk_announcement',
            with: [
                'mailSubject' => $this->mailSubject,
                'mailContent' => $this->mailContent,
                'recipientName' => $this->recipientName,
                'imageUrl' => null,
            ],
        );
    }
}

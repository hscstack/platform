<?php

namespace App\Mail;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BlogReactionMilestoneMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Blog $blog,
        public User $reactor,
        public int $milestoneCount,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->milestoneCount === 1
            ? 'Your blog received its first love reaction! ❤️'
            : "🎉 Milestone: {$this->milestoneCount} people loved your blog post!";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $blogUrl = rtrim($appUrl, '/')."/blogs/{$this->blog->slug}";
        $reactorName = htmlspecialchars($this->reactor->name, ENT_QUOTES, 'UTF-8');
        $blogTitle = htmlspecialchars($this->blog->title, ENT_QUOTES, 'UTF-8');

        if ($this->milestoneCount === 1) {
            $headline = 'Congratulations! Your blog post received its very first love reaction ❤️';
            $message = "<p><strong>{$reactorName}</strong> just loved your article <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a>.</p>"
                .'<p>Readers are appreciating your work! Keep sharing knowledge with the community.</p>';
        } else {
            $headline = "🎉 Exciting News! {$this->milestoneCount} Love Reactions Milestone reached!";
            $message = "<p>Your article <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a> just hit <strong>{$this->milestoneCount} love reactions</strong>, with the latest from <strong>{$reactorName}</strong>!</p>"
                .'<p>Thank you for creating content that resonates with our students and readers.</p>';
        }

        $mailContent = "<p style=\"font-size: 16px; font-weight: 700; color: #4f46e5;\">{$headline}</p>"
            .$message
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$blogUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View Blog Post &rarr;'
            .'</a>'
            .'</p>';

        return new Content(
            view: 'emails.bulk_announcement',
            with: [
                'mailSubject' => $this->milestoneCount === 1 ? 'First Reaction on Your Blog' : "{$this->milestoneCount} Reactions Milestone",
                'mailContent' => $mailContent,
                'recipientName' => $this->blog->user?->name,
                'imageUrl' => null,
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Blog;
use App\Models\BlogComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BlogCommentNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Blog $blog,
        public BlogComment $comment,
    ) {}

    public function envelope(): Envelope
    {
        $commenterName = $this->comment->user?->name ?? 'Someone';

        return new Envelope(
            subject: "New comment on your blog: \"{$this->blog->title}\"",
        );
    }

    public function content(): Content
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $blogUrl = rtrim($appUrl, '/') . "/blogs/{$this->blog->slug}#comments";
        $commenterName = htmlspecialchars($this->comment->user?->name ?? 'A reader', ENT_QUOTES, 'UTF-8');
        $commentContent = nl2br(htmlspecialchars($this->comment->content, ENT_QUOTES, 'UTF-8'));
        $blogTitle = htmlspecialchars($this->blog->title, ENT_QUOTES, 'UTF-8');

        $mailContent = "<p><strong>{$commenterName}</strong> just commented on your blog post <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a>:</p>"
            . "<blockquote style=\"border-left: 4px solid #4f46e5; background-color: #f8fafc; padding: 14px 18px; margin: 18px 0; border-radius: 0 8px 8px 0; font-style: normal; color: #334155;\">"
            . "<p style=\"margin: 0; font-size: 14px; line-height: 1.6;\">{$commentContent}</p>"
            . "</blockquote>"
            . "<p style=\"margin-top: 24px;\">"
            . "<a href=\"{$blogUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            . "View & Reply to Comment &rarr;"
            . "</a>"
            . "</p>";

        return new Content(
            view: 'emails.bulk_announcement',
            with: [
                'mailSubject' => "New comment on \"{$this->blog->title}\"",
                'mailContent' => $mailContent,
                'recipientName' => $this->blog->user?->name,
                'imageUrl' => null,
            ],
        );
    }
}

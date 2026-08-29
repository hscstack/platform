<?php

namespace App\Mail;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BlogNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailContent,
        public ?string $recipientName = null,
    ) {}

    public static function forComment(Blog $blog, BlogComment $comment): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $blogUrl = rtrim($appUrl, '/')."/blogs/{$blog->slug}#comments";
        $commenterName = htmlspecialchars($comment->user?->name ?? 'A reader', ENT_QUOTES, 'UTF-8');
        $commentContent = nl2br(htmlspecialchars($comment->content, ENT_QUOTES, 'UTF-8'));
        $blogTitle = htmlspecialchars($blog->title, ENT_QUOTES, 'UTF-8');

        $mailSubject = "New comment on \"{$blog->title}\"";
        $mailContent = "<p><strong>{$commenterName}</strong> just commented on your blog post <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a>:</p>"
            .'<blockquote style="border-left: 4px solid #4f46e5; background-color: #f8fafc; padding: 14px 18px; margin: 18px 0; border-radius: 0 8px 8px 0; font-style: normal; color: #334155;">'
            ."<p style=\"margin: 0; font-size: 14px; line-height: 1.6;\">{$commentContent}</p>"
            .'</blockquote>'
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$blogUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View & Reply to Comment &rarr;'
            .'</a>'
            .'</p>';

        return new self($mailSubject, $mailContent, $blog->user?->name);
    }

    public static function forReply(Blog $blog, BlogComment $reply, BlogComment $parentComment): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $blogUrl = rtrim($appUrl, '/')."/blogs/{$blog->slug}#comments";
        $replierName = htmlspecialchars($reply->user?->name ?? 'Someone', ENT_QUOTES, 'UTF-8');
        $replyContent = nl2br(htmlspecialchars($reply->content, ENT_QUOTES, 'UTF-8'));
        $blogTitle = htmlspecialchars($blog->title, ENT_QUOTES, 'UTF-8');

        $mailSubject = "New reply to your comment on \"{$blog->title}\"";
        $mailContent = "<p><strong>{$replierName}</strong> replied to your comment on <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a>:</p>"
            .'<blockquote style="border-left: 4px solid #4f46e5; background-color: #f8fafc; padding: 14px 18px; margin: 18px 0; border-radius: 0 8px 8px 0; font-style: normal; color: #334155;">'
            ."<p style=\"margin: 0; font-size: 14px; line-height: 1.6;\">{$replyContent}</p>"
            .'</blockquote>'
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$blogUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View & Reply &rarr;'
            .'</a>'
            .'</p>';

        return new self($mailSubject, $mailContent, $parentComment->user?->name);
    }

    public static function forReactionMilestone(Blog $blog, User $reactor, int $milestoneCount): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $blogUrl = rtrim($appUrl, '/')."/blogs/{$blog->slug}";
        $reactorName = htmlspecialchars($reactor->name, ENT_QUOTES, 'UTF-8');
        $blogTitle = htmlspecialchars($blog->title, ENT_QUOTES, 'UTF-8');

        if ($milestoneCount === 1) {
            $mailSubject = 'Your blog received its first love reaction! ❤️';
            $headline = 'Congratulations! Your blog post received its very first love reaction ❤️';
            $message = "<p><strong>{$reactorName}</strong> just loved your article <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a>.</p>"
                .'<p>Readers are appreciating your work! Keep sharing knowledge with the community.</p>';
        } else {
            $mailSubject = "🎉 Milestone: {$milestoneCount} people loved your blog post!";
            $headline = "🎉 Exciting News! {$milestoneCount} Love Reactions Milestone reached!";
            $message = "<p>Your article <a href=\"{$blogUrl}\" target=\"_blank\"><strong>\"{$blogTitle}\"</strong></a> just hit <strong>{$milestoneCount} love reactions</strong>, with the latest from <strong>{$reactorName}</strong>!</p>"
                .'<p>Thank you for creating content that resonates with our students and readers.</p>';
        }

        $mailContent = "<p style=\"font-size: 16px; font-weight: 700; color: #4f46e5;\">{$headline}</p>"
            .$message
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$blogUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View Blog Post &rarr;'
            .'</a>'
            .'</p>';

        return new self($mailSubject, $mailContent, $blog->user?->name);
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

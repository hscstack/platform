<?php

namespace App\Mail;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForumNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailContent,
        public ?string $recipientName = null,
    ) {}

    public static function forAnswer(ForumPost $post, ForumAnswer $answer, User $author, User $responder): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $postUrl = rtrim($appUrl, '/')."/forum/questions/{$post->slug}";
        $responderName = htmlspecialchars($responder->name, ENT_QUOTES, 'UTF-8');
        $bodyExcerpt = nl2br(htmlspecialchars(str($answer->body)->limit(300), ENT_QUOTES, 'UTF-8'));
        $postTitle = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');

        $mailSubject = "New answer on your question: \"{$post->title}\"";
        $mailContent = "<p><strong>{$responderName}</strong> just posted an answer to your question <a href=\"{$postUrl}\" target=\"_blank\"><strong>\"{$postTitle}\"</strong></a>:</p>"
            .'<blockquote style="border-left: 4px solid #4f46e5; background-color: #f8fafc; padding: 14px 18px; margin: 18px 0; border-radius: 0 8px 8px 0; font-style: normal; color: #334155;">'
            ."<p style=\"margin: 0; font-size: 14px; line-height: 1.6;\">{$bodyExcerpt}</p>"
            .'</blockquote>'
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$postUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View Answer in Forum &rarr;'
            .'</a>'
            .'</p>';

        return new self($mailSubject, $mailContent, $author->name);
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

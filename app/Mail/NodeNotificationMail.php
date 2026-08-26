<?php

namespace App\Mail;

use App\Models\Node;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NodeNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailContent,
        public ?string $recipientName = null,
    ) {}

    public static function forUpvoteMilestone(Node $node, User $upvoter, int $milestoneCount): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');

        $slugs = [];
        $curr = $node;
        while ($curr) {
            array_unshift($slugs, $curr->slug);
            $curr = $curr->parent;
        }

        $folderPath = $node->subject ? "{$node->subject->slug}/".implode('/', $slugs) : '';
        $folderUrl = rtrim($appUrl, '/').'/'.$folderPath;

        $upvoterName = htmlspecialchars($upvoter->name, ENT_QUOTES, 'UTF-8');
        $folderTitle = htmlspecialchars($node->name, ENT_QUOTES, 'UTF-8');
        $subjectName = $node->subject ? htmlspecialchars($node->subject->name, ENT_QUOTES, 'UTF-8') : '';
        $context = $subjectName ? "in {$subjectName}" : '';

        if ($milestoneCount === 1) {
            $mailSubject = 'Your folder received its first upvote! 🚀';
            $headline = 'Congratulations! Your folder received its very first upvote 🚀';
            $message = "<p><strong>{$upvoterName}</strong> just upvoted your folder <a href=\"{$folderUrl}\" target=\"_blank\"><strong>\"{$folderTitle}\"</strong></a>".($context ? " {$context}" : '').'.</p>'
                .'<p>Students and contributors are finding your curated materials helpful! Keep sharing and organizing knowledge for the community.</p>';
        } else {
            $mailSubject = "🎉 Milestone: {$milestoneCount} people upvoted your folder!";
            $headline = "🎉 Exciting News! {$milestoneCount} Upvotes Milestone reached!";
            $message = "<p>Your folder <a href=\"{$folderUrl}\" target=\"_blank\"><strong>\"{$folderTitle}\"</strong></a> just hit <strong>{$milestoneCount} upvotes</strong>, with the latest from <strong>{$upvoterName}</strong>!</p>"
                .'<p>Thank you for organizing study materials that help fellow students excel.</p>';
        }

        $mailContent = "<p style=\"font-size: 16px; font-weight: 700; color: #4f46e5;\">{$headline}</p>"
            .$message
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$folderUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 20px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View Folder &rarr;'
            .'</a>'
            .'</p>';

        return new self($mailSubject, $mailContent, $node->user?->name);
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

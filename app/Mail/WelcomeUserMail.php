<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to HSCStack — The Open Learning Platform',
        );
    }

    public function content(): Content
    {
        $appUrl = config('app.url', 'https://hscstack.site');

        $mailContent = '<p>Welcome to <strong>HSCStack</strong>! We are thrilled to have you join our open learning community.</p>'
            .'<p>Here is what you can do on HSCStack:</p>'
            .'<ul>'
            .'<li><strong>Free Study Resources:</strong> Access structured chapter notes, formulas, lecture videos, and problem solutions for HSC and SSC curricula.</li>'
            .'<li><strong>HSCStack AI:</strong> Try our smart interactive learning assistant to get instant explanations on complex topics and formulas (<a href="'.$appUrl.'/ai" target="_blank">Explore AI</a>).</li>'
            .'<li><strong>Share & Shorten Links:</strong> Generate clean short links for any subject, chapter, or blog post with our floating share tool.</li>'
            .'<li><strong>Blogs & Insights:</strong> Read educational guides and exam preparation tips written by top educators.</li>'
            .'</ul>'
            .'<blockquote>'
            .'<strong>Want to contribute?</strong> Join our team as a contributor, content writer, or editor to help thousands of students across Bangladesh. Check out <a href="'.$appUrl.'/join" target="_blank">Join Our Team</a> and the <a href="'.$appUrl.'/guide" target="_blank">Contributor Guide</a>.'
            .'</blockquote>'
            .'<p>If you have any questions or feedback, feel free to visit our <a href="'.$appUrl.'/support" target="_blank">Support Page</a>.</p>'
            .'<p>Happy learning!<br><strong>The HSCStack Team</strong></p>';

        return new Content(
            view: 'emails.bulk_announcement',
            with: [
                'mailSubject' => 'Welcome to HSCStack — The Open Learning Platform',
                'mailContent' => $mailContent,
                'recipientName' => $this->user->name,
                'imageUrl' => null,
            ],
        );
    }
}

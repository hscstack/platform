<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'আপনার অ্যাকাউন্ট ডিলিট করা হয়েছে',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_deleted',
            with: [
                'recipientName' => $this->recipientName,
                'privacyPolicyUrl' => url('/privacy-policy'),
                'termsConditionsUrl' => url('/terms-service'),
                'appUrl' => config('app.url', url('/')),
            ],
        );
    }
}

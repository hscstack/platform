<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $signedUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'আপনার অ্যাকাউন্ট মুছে ফেলার কনফার্মেশন লিংক',
        );
    }

    public function content(): Content
    {
        $name = $this->user->name ? "Hello {$this->user->name}," : 'প্রিয় শিক্ষার্থী,';

        return new Content(
            view: 'emails.default',
            with: [
                'subject' => 'আপনার অ্যাকাউন্ট মুছে ফেলার কনফার্মেশন লিংক',
                'greeting' => $name,
                'lines' => [
                    'অ্যাডমিন আপনার অ্যাকাউন্ট স্থায়ীভাবে মুছে ফেলার জন্য একটি কনফার্মেশন লিংক পাঠিয়েছেন।',
                    'আপনি যদি আপনার HSCStack অ্যাকাউন্টটি ডিলিট করতে চান, তাহলে নিচের বাটনে ক্লিক করে কনফার্মেশন সম্পন্ন করুন।',
                    '<strong>সতর্কতা:</strong> এই লিংকের মেয়াদ আগামী <strong>৭ দিন</strong> পর্যন্ত কার্যকর থাকবে। অ্যাকাউন্ট মুছে ফেলার পর আপনার সব ডাটা স্থায়ীভাবে ডিলিট হয়ে যাবে এবং তা আর পুনরুদ্ধার করা সম্ভব হবে না।',
                    'আপনি যদি অ্যাকাউন্ট ডিলিট করতে না চান, তবে এই ইমেইলটি উপেক্ষা করুন। আপনার অ্যাকাউন্ট সুরক্ষিত থাকবে।',
                ],
                'actionText' => 'অ্যাকাউন্ট ডিলিট করুন (Confirm Deletion)',
                'actionUrl' => $this->signedUrl,
            ],
        );
    }
}

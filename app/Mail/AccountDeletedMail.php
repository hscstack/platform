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
        $name = $this->recipientName ? "প্রিয় {$this->recipientName}," : 'প্রিয় শিক্ষার্থী,';
        $privacyUrl = url('/privacy-policy');
        $termsUrl = url('/terms-service');
        $archiveUrl = url('/');
        $forumUrl = url('/forum');
        $chatUrl = url('/chat');
        $trackerUrl = url('/tracker');

        return new Content(
            view: 'emails.default',
            with: [
                'subject' => 'আপনার অ্যাকাউন্ট ডিলিট করা হয়েছে',
                'greeting' => $name,
                'lines' => [
                    "আমরা আপনার অ্যাকাউন্ট ডিলিট করার অনুরোধটি পেয়েছি। আপনার অ্যাকাউন্ট এবং এর সাথে সম্পর্কিত যাবতীয় তথ্য আমাদের <a href=\"{$privacyUrl}\" target=\"_blank\">Privacy Policy</a> ও <a href=\"{$termsUrl}\" target=\"_blank\">Terms & Conditions</a> অনুযায়ী স্থায়ীভাবে মুছে ফেলা হয়েছে।",
                    'ভবিষ্যতে যদি আবার HSCStack ব্যবহার করতে চান, তাহলে যেকোনো সময় নতুন করে একটি অ্যাকাউন্ট তৈরি করে আমাদের প্ল্যাটফর্মে ফিরে আসতে পারেন।',
                    '<strong>HSCStack-এর উল্লেখযোগ্য ফিচারসমূহ:</strong>',
                    "• <a href=\"{$archiveUrl}\" target=\"_blank\">Resource Archive</a> — HSC ও SSC-এর Science, Arts এবং Commerce বিভাগের ক্লাস, নোট ও বিভিন্ন শিক্ষামূলক রিসোর্স।",
                    "• <a href=\"{$forumUrl}\" target=\"_blank\">Forum</a> — যেকোনো প্রশ্ন করতে পারবেন এবং সহপাঠীদের কাছ থেকে উত্তর ও সহযোগিতা পেতে পারবেন।",
                    "• <a href=\"{$chatUrl}\" target=\"_blank\">Global Chat</a> — সারাদেশের শিক্ষার্থীদের সাথে যোগাযোগ ও মতবিনিময়ের সুযোগ।",
                    "• <a href=\"{$trackerUrl}\" target=\"_blank\">Study Tracker</a> — আপনার পড়াশোনা ও সিলেবাসের অগ্রগতি সহজেই ট্র্যাক করুন।",
                    '• Community Interaction — অন্যান্য শিক্ষার্থীদের সাথে যুক্ত হয়ে আলোচনা, সহযোগিতা ও জ্ঞান বিনিময় করুন।',
                    'আবারও ধন্যবাদ HSCStack-এর সাথে থাকার জন্য।',
                    'ভবিষ্যতে আবার দেখা হবে! 💙',
                ],
                'actionText' => 'Visit HSCStack',
                'actionUrl' => config('app.url', url('/')),
            ],
        );
    }
}

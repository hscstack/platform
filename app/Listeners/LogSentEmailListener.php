<?php

namespace App\Listeners;

use App\Models\SentEmailLog;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

class LogSentEmailListener
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $toAddresses = array_map(fn (Address $addr) => $addr->getAddress(), $message->getTo());
        $recipientEmail = implode(', ', $toAddresses);

        $toNames = array_filter(array_map(fn (Address $addr) => $addr->getName(), $message->getTo()));
        $recipientName = ! empty($toNames) ? implode(', ', $toNames) : null;

        SentEmailLog::create([
            'recipient_email' => $recipientEmail ?: 'unknown@example.com',
            'recipient_name' => $recipientName,
            'subject' => $message->getSubject() ?? '(No Subject)',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}

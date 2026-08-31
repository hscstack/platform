<?php

namespace App\Listeners;

use App\Models\SentEmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

class LogSentEmailListener
{
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;

            $toAddresses = array_map(fn (Address $addr) => $addr->getAddress(), $message->getTo() ?? []);
            $recipientEmail = implode(', ', $toAddresses);

            $toNames = array_filter(array_map(fn (Address $addr) => $addr->getName(), $message->getTo() ?? []));
            $recipientName = ! empty($toNames) ? implode(', ', $toNames) : null;

            SentEmailLog::create([
                'recipient_email' => $recipientEmail ?: 'unknown@example.com',
                'recipient_name' => $recipientName,
                'subject' => $message->getSubject() ?? '(No Subject)',
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record sent email log: '.$e->getMessage());
        }
    }

    public function handleJobFailed(JobFailed $event): void
    {
        if ($event->job->resolveName() !== SendQueuedMailable::class) {
            return;
        }

        try {
            $payload = $event->job->payload();
            $command = unserialize($payload['data']['command'] ?? '');
            $mailable = $command->mailable ?? null;

            if ($mailable) {
                $to = collect($mailable->to ?? [])->pluck('address')->filter()->implode(', ');
                $subject = $mailable->subject ?? '(No Subject)';

                SentEmailLog::create([
                    'recipient_email' => $to ?: 'unknown@example.com',
                    'subject' => $subject,
                    'status' => 'failed',
                    'error_message' => str($event->exception->getMessage())->limit(500),
                    'sent_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to record failed email log: '.$e->getMessage());
        }
    }
}

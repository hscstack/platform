<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailContent,
        public ?string $recipientName = null,
    ) {}

    public static function forCreated(SupportTicket $ticket): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $ticketsUrl = rtrim($appUrl, '/').'/support/my-tickets';
        $userName = htmlspecialchars($ticket->user?->name ?? 'User', ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($ticket->subject, ENT_QUOTES, 'UTF-8');
        $ticketNumber = htmlspecialchars($ticket->ticket_number, ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars(ucwords(str_replace('_', ' ', $ticket->category)), ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars($ticket->message, ENT_QUOTES, 'UTF-8'));

        $mailSubject = "[HSCStack Support] Ticket #{$ticket->ticket_number} Received";
        $mailContent = '<p style="font-size: 16px; font-weight: 700; color: #4f46e5;">We received your support request!</p>'
            ."<p>Hello <strong>{$userName}</strong>,</p>"
            ."<p>Thank you for contacting us. We have received your support request and assigned it ticket number <strong>#{$ticketNumber}</strong>. Our support team will review your query and reply shortly.</p>"
            .'<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin: 20px 0;">'
            ."<p style=\"margin: 0 0 8px 0; font-size: 13px; color: #64748b;\"><strong>Ticket ID:</strong> {$ticketNumber}</p>"
            ."<p style=\"margin: 0 0 8px 0; font-size: 13px; color: #64748b;\"><strong>Category:</strong> {$category}</p>"
            ."<p style=\"margin: 0 0 8px 0; font-size: 13px; color: #64748b;\"><strong>Subject:</strong> {$subject}</p>"
            ."<p style=\"margin: 0; font-size: 13px; color: #334155; line-height: 1.5;\"><strong>Message:</strong><br>{$message}</p>"
            .'</div>'
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$ticketsUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 22px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View My Tickets &rarr;'
            .'</a>'
            .'</p>'
            .'<p style="color: #64748b; font-size: 13px; margin-top: 24px;">Warm regards,<br><strong>HSCStack Support Team</strong></p>';

        return new self($mailSubject, $mailContent, $ticket->user?->name);
    }

    public static function forStatusUpdated(SupportTicket $ticket): self
    {
        $appUrl = config('app.url', 'https://hscstack.site');
        $ticketsUrl = rtrim($appUrl, '/').'/support/my-tickets';
        $userName = htmlspecialchars($ticket->user?->name ?? 'User', ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($ticket->subject, ENT_QUOTES, 'UTF-8');
        $ticketNumber = htmlspecialchars($ticket->ticket_number, ENT_QUOTES, 'UTF-8');
        $statusLabel = ucwords(str_replace('_', ' ', $ticket->status));
        $reply = $ticket->admin_reply ? nl2br(htmlspecialchars($ticket->admin_reply, ENT_QUOTES, 'UTF-8')) : null;

        $mailSubject = "[HSCStack Support] Ticket #{$ticket->ticket_number} Updated ({$statusLabel})";

        $replySection = $reply
            ? '<div style="background-color: #eef2ff; border-left: 4px solid #4f46e5; border-radius: 0 10px 10px 0; padding: 16px 20px; margin: 20px 0;">'
                .'<p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: #4f46e5; text-transform: uppercase;">Support Team Response:</p>'
                ."<p style=\"margin: 0; font-size: 14px; color: #1e293b; line-height: 1.6;\">{$reply}</p>"
                .'</div>'
            : '';

        $mailContent = '<p style="font-size: 16px; font-weight: 700; color: #4f46e5;">Your support ticket has been updated</p>'
            ."<p>Hello <strong>{$userName}</strong>,</p>"
            ."<p>Your support ticket <strong>#{$ticketNumber}</strong> (<em>{$subject}</em>) status has been updated to <strong>{$statusLabel}</strong>.</p>"
            .$replySection
            .'<p style="margin-top: 24px;">'
            ."<a href=\"{$ticketsUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 10px 22px; font-weight: 600; text-decoration: none; border-radius: 10px; font-size: 13px;\">"
            .'View Ticket in Support Portal &rarr;'
            .'</a>'
            .'</p>'
            .'<p style="color: #64748b; font-size: 13px; margin-top: 24px;">If you have any further questions, please let us know.<br><strong>HSCStack Support Team</strong></p>';

        return new self($mailSubject, $mailContent, $ticket->user?->name);
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

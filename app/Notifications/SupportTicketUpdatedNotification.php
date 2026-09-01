<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SupportTicketUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email) && ($notifiable->receive_emails ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst(str_replace('_', ' ', $this->ticket->status));
        $lines = [
            "Your support ticket #{$this->ticket->ticket_number} (\"{$this->ticket->subject}\") has an update from our team.",
            "Current Status: {$statusLabel}",
        ];

        if ($this->ticket->admin_reply) {
            $lines[] = "Staff Reply: \"{$this->ticket->admin_reply}\"";
        }

        return (new MailMessage)
            ->subject("Support Ticket #{$this->ticket->ticket_number} Updated 🎫")
            ->view('emails.default', [
                'subject' => "Ticket #{$this->ticket->ticket_number} Updated",
                'greeting' => "Hello {$notifiable->name},",
                'lines' => $lines,
                'actionText' => 'View My Tickets',
                'actionUrl' => route('support.my-tickets'),
            ]);
    }

    /**
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusLabel = ucfirst(str_replace('_', ' ', $this->ticket->status));
        $snippet = $this->ticket->admin_reply ? Str::limit(strip_tags($this->ticket->admin_reply), 80) : null;
        $message = $snippet
            ? "Staff replied: \"{$snippet}\""
            : "Status updated to {$statusLabel}";

        return [
            'type' => 'support_ticket',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => "Ticket #{$this->ticket->ticket_number} Updated",
            'message' => $message,
            'url' => route('support.my-tickets'),
            'status' => $this->ticket->status,
        ];
    }
}

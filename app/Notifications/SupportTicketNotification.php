<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SupportTicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public bool $hasReply = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        if ($this->hasReply) {
            $title = "Admin replied to ticket #{$this->ticket->ticket_number}";
            $message = Str::limit(strip_tags($this->ticket->admin_reply ?? 'New reply received on your ticket.'), 90);
        } else {
            $statusFormatted = ucfirst(str_replace('_', ' ', $this->ticket->status));
            $title = "Ticket #{$this->ticket->ticket_number} status updated";
            $message = "Your ticket has been marked as {$statusFormatted}.";
        }

        return [
            'type' => 'support_ticket',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'status' => $this->ticket->status,
            'title' => $title,
            'message' => $message,
            'url' => route('support.my-tickets'),
        ];
    }
}

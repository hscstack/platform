<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

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
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $statusLabel = ucfirst(str_replace('_', ' ', $this->ticket->status));
        $snippet = $this->ticket->admin_reply ? Str::limit(strip_tags($this->ticket->admin_reply), 80) : null;
        $body = $snippet
            ? "Staff replied: \"{$snippet}\""
            : "Status updated to {$statusLabel}";

        return (new WebPushMessage)
            ->title("Support Ticket #{$this->ticket->ticket_number} Updated")
            ->icon('/favicon.png')
            ->body($body)
            ->action('View Ticket', 'view_ticket')
            ->data(['url' => route('support.my-tickets')]);
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

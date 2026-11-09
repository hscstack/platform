<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public User $user,
        public int $count = 1,
    ) {}

    /**
     * Send or roll up a support ticket notification to staff users.
     *
     * @param  Collection<int, User>  $staffMembers
     */
    public static function sendTo(Collection $staffMembers, SupportTicket $ticket, User $user): void
    {
        foreach ($staffMembers as $staff) {
            $unread = $staff->unreadNotifications()
                ->where('type', self::class)
                ->first();

            if ($unread) {
                $data = $unread->data;
                $count = ($data['count'] ?? 1) + 1;

                $data['count'] = $count;
                $data['title'] = "{$count} New Support Tickets";
                $data['message'] = "Latest from {$user->name}: {$ticket->subject}";
                $data['ticket_id'] = $ticket->id;
                $data['ticket_number'] = $ticket->ticket_number;
                $data['url'] = url('/admin/tickets');

                $unread->update([
                    'data' => $data,
                    'created_at' => now(),
                ]);
            } else {
                $staff->notify(new self($ticket, $user, 1));
            }
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_ticket',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => "New Support Ticket #{$this->ticket->ticket_number}",
            'message' => "{$this->user->name}: {$this->ticket->subject}",
            'url' => url('/admin/tickets'),
            'count' => $this->count,
        ];
    }
}

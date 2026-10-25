<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class UserAppreciationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $appreciator,
        public int $totalAppreciations,
    ) {}

    /**
     * Send or roll up an appreciation notification to the target user.
     */
    public static function sendTo(User $targetUser, User $appreciator, int $totalAppreciations): void
    {
        if ($targetUser->id === $appreciator->id) {
            return;
        }

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->first();

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;
            $others = $count - 1;
            $othersText = $others === 1 ? '1 other person' : "{$others} other people";

            $data['count'] = $count;
            $data['title'] = "{$appreciator->name} and {$othersText} appreciated you!";
            $data['message'] = "You have {$totalAppreciations} community appreciations.";
            $data['total_appreciations'] = $totalAppreciations;

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);
        } else {
            $targetUser->notify(new self($appreciator, $totalAppreciations));
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
            'type' => 'user_appreciation',
            'title' => "{$this->appreciator->name} appreciated you!",
            'message' => "You have {$this->totalAppreciations} community appreciations.",
            // Deep link to user's profile with #appreciators hash so the appreciation modal auto-opens
            'url' => route('user.profile', $notifiable->username).'#appreciators',
            'appreciator_id' => $this->appreciator->id,
            'total_appreciations' => $this->totalAppreciations,
            'count' => 1,
        ];
    }
}

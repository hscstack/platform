<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserAppreciationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $appreciator,
        public int $totalAppreciations = 1
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_appreciation',
            'title' => "{$this->appreciator->name} appreciated your profile!",
            'message' => "You have received {$this->totalAppreciations} total appreciations on your profile.",
            'url' => route('user.profile', ['username' => $notifiable->username]),
            'appreciator_name' => $this->appreciator->name,
            'appreciator_username' => $this->appreciator->username,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StudyPokeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $sender,
        public string $message,
        public string $icon = '⚡',
        public ?string $presetId = null,
    ) {}

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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'study_poke',
            'title' => "{$this->sender->name} poked you! {$this->icon}",
            'message' => $this->message,
            'url' => route('user.profile', ['username' => $this->sender->username]),
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_username' => $this->sender->username,
            'sender_image' => $this->sender->image_url,
            'poke_icon' => $this->icon,
            'poke_message' => $this->message,
            'preset_id' => $this->presetId,
        ];
    }
}

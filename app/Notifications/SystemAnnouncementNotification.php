<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SystemAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $snippet,
        public ?string $fullContent = null,
        public ?string $imageUrl = null,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_announcement',
            'title' => $this->title,
            'message' => $this->snippet,
            'full_content' => $this->fullContent,
            'image_url' => $this->imageUrl,
            'url' => $this->url,
        ];
    }
}

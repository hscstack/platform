<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

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
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->icon($this->imageUrl ?: '/favicon.png')
            ->body($this->snippet)
            ->action('Open', 'open_announcement');

        if ($this->url) {
            $message->data(['url' => $this->url]);
        }

        return $message;
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

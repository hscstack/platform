<?php

namespace App\Notifications;

use App\Models\Resource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ResourceModerationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Resource $resource,
        public string $status,
        public ?string $feedback = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $url = $this->status === 'rejected'
            ? '/my-submissions?status=rejected'
            : '/my-submissions?status=approved';

        $resourceTitle = $this->resource->title ?? 'Resource';

        $title = $this->status === 'rejected'
            ? 'Resource Submission Rejected'
            : 'Resource Submission Approved';

        $message = $this->status === 'rejected'
            ? ($this->feedback ? "\"{$resourceTitle}\": {$this->feedback}" : "Your resource \"{$resourceTitle}\" was rejected.")
            : "Your resource \"{$resourceTitle}\" was approved and is now live.";

        return [
            'type' => 'resource_moderation',
            'status' => $this->status,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'resource_id' => $this->resource->id,
            'node_id' => $this->resource->node_id,
        ];
    }
}

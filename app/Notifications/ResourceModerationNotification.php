<?php

namespace App\Notifications;

use App\Models\ResourceChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResourceModerationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ResourceChangeRequest $changeRequest,
        public string $status,
        public ?string $feedback = null,
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
     * Get the array representation of the notification (Database in-app bell).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actionName = match ($this->changeRequest->action_type) {
            'create' => 'upload',
            'update' => 'edit',
            'delete' => 'deletion request',
            default => 'request',
        };

        $resourceTitle = $this->changeRequest->payload['title']
            ?? $this->changeRequest->resource?->title
            ?? 'Resource';

        $title = $this->status === 'rejected'
            ? 'Resource '.ucfirst($actionName).' Rejected'
            : 'Resource '.ucfirst($actionName).' Approved';

        $message = $this->status === 'rejected'
            ? ($this->feedback ? "\"{$resourceTitle}\": {$this->feedback}" : "Your {$actionName} for \"{$resourceTitle}\" was rejected.")
            : "Your {$actionName} for \"{$resourceTitle}\" was approved and is now live.";

        $node = $this->changeRequest->node;
        if ($node && $node->subject) {
            $path = implode('/', array_column($node->breadcrumb(), 'slug'));
            $url = "/admin/subjects/{$node->subject->slug}/nodes/{$path}";
        } else {
            $url = '/admin';
        }

        return [
            'type' => 'resource_moderation',
            'status' => $this->status,
            'action_type' => $this->changeRequest->action_type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'change_request_id' => $this->changeRequest->id,
            'node_id' => $this->changeRequest->node_id,
        ];
    }
}

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
        public int $totalCount = 1,
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
        $node = $this->changeRequest->node;
        if ($node && $node->subject) {
            $path = implode('/', array_column($node->breadcrumb(), 'slug'));
            $hash = $this->status === 'rejected' ? '#rejected' : '#live';
            $url = "/admin/subjects/{$node->subject->slug}/nodes/{$path}{$hash}";
        } else {
            $url = '/admin';
        }

        if ($this->totalCount > 1) {
            $title = $this->status === 'rejected'
                ? "{$this->totalCount} Resource Requests Rejected"
                : "{$this->totalCount} Resource Requests Approved";

            $message = $this->status === 'rejected'
                ? ($this->feedback ? "{$this->totalCount} of your resource requests were rejected: {$this->feedback}" : "{$this->totalCount} of your resource requests were rejected.")
                : "{$this->totalCount} of your resource requests were approved and are now live.";

            return [
                'type' => 'resource_moderation',
                'status' => $this->status,
                'action_type' => 'batch',
                'title' => $title,
                'message' => $message,
                'url' => $url,
                'count' => $this->totalCount,
                'change_request_id' => $this->changeRequest->id,
                'node_id' => $this->changeRequest->node_id,
            ];
        }

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

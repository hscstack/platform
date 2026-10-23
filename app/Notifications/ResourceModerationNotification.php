<?php

namespace App\Notifications;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ResourceModerationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Resource $resource,
        public string $status,
        public ?string $feedback = null,
    ) {}

    /**
     * Send or roll up a moderation notification to the target user.
     */
    public static function sendTo(User $targetUser, Resource $resource, string $status, ?string $feedback = null): void
    {
        if ($targetUser->id === Auth::id()) {
            return;
        }

        $unread = $targetUser->unreadNotifications()
            ->where('type', self::class)
            ->where('data->status', $status)
            ->first();

        if ($unread) {
            $data = $unread->data;
            $count = ($data['count'] ?? 1) + 1;

            $data['count'] = $count;
            if ($status === 'approved') {
                $data['title'] = 'Resource Submissions Approved';
                $data['message'] = "{$count} of your resource submissions were approved and are now live.";
            } else {
                $data['title'] = 'Resource Submissions Rejected';
                $data['message'] = $feedback
                    ? "{$count} resource submissions were rejected: {$feedback}"
                    : "{$count} of your resource submissions were rejected.";
            }

            $unread->update([
                'data' => $data,
                'created_at' => now(),
            ]);
        } else {
            $targetUser->notify(new self($resource, $status, $feedback));
        }
    }

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
            'count' => 1,
        ];
    }
}

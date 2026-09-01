<?php

namespace App\Notifications;

use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ChatReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Report $report,
        public User $reporter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $snippet = Str::limit($this->report->content_snapshot, 60);

        return [
            'type' => 'chat_report',
            'title' => 'Chat message reported',
            'message' => "@{$this->reporter->username} reported: \"{$snippet}\"",
            'url' => route('admin.chat.reports.index'),
            'report_id' => $this->report->id,
        ];
    }
}

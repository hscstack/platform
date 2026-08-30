<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_BUG_REPORT = 'bug_report';

    public const CATEGORY_MISSING_RESOURCE = 'missing_resource';

    public const CATEGORY_ACCOUNT_ISSUE = 'account_issue';

    public const CATEGORY_SUGGESTION = 'suggestion';

    public const CATEGORY_OTHER = 'other';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'category',
        'subject',
        'message',
        'attachment_path',
        'status',
        'admin_reply',
        'replied_by',
        'replied_at',
    ];

    protected $appends = [
        'attachment_url',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return str($this->attachment_path)->startsWith(['http://', 'https://'])
            ? $this->attachment_path
            : Storage::url($this->attachment_path);
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'TKT-'.date('Y').'-'.strtoupper(Str::random(5));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}

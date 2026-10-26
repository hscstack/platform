<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reported_user_name',
        'reported_user_username',
        'reportable_type',
        'reportable_id',
        'content_snapshot',
        'message_sent_at',
        'reason',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
        'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'message_sent_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}

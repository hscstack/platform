<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'reply_to_id',
        'reply_to_content',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    /**
     * Format reactions as an aggregated array.
     */
    public function getFormattedReactions(?int $currentUserId = null): array
    {
        $reactions = $this->relationLoaded('reactions')
            ? $this->reactions
            : $this->reactions()->with('user:id,name,username')->get();

        return $reactions
            ->groupBy('emoji')
            ->map(function ($group, $emoji) use ($currentUserId) {
                return [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'reacted' => $currentUserId ? $group->contains('user_id', $currentUserId) : false,
                    'users' => $group->map(fn ($r) => $r->user?->username ?? $r->user?->name)->filter()->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Keep only the latest N messages in the database.
     */
    public static function pruneOldMessages(int $keepCount = 200): void
    {
        $cutoffId = static::latest('id')->skip($keepCount)->value('id');
        if ($cutoffId) {
            static::where('id', '<=', $cutoffId)->delete();
        }
    }
}

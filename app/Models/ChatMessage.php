<?php

namespace App\Models;

use App\Events\ChatMessageSent;
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
            : $this->reactions()->with(['user:id,name,username,image_path,institution', 'user.roles:id,name'])->get();

        return $reactions
            ->groupBy('emoji')
            ->map(function ($group, $emoji) use ($currentUserId) {
                return [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'reacted' => $currentUserId ? $group->contains('user_id', $currentUserId) : false,
                    'users' => $group->map(fn ($r) => $r->user?->username ?? $r->user?->name)->filter()->values()->toArray(),
                    'reactors' => $group->map(function ($r) {
                        if (! $r->user) {
                            return null;
                        }

                        return [
                            'id' => $r->user->id,
                            'name' => $r->user->name,
                            'username' => $r->user->username,
                            'image_url' => $r->user->image_url,
                            'image_path' => $r->user->image_path,
                            'institution' => $r->user->institution,
                            'is_verified' => (bool) ($r->user->is_verified || ($r->user->relationLoaded('roles') && $r->user->roles->isNotEmpty())),
                            'roles' => $r->user->relationLoaded('roles') ? $r->user->roles->pluck('name')->toArray() : [],
                        ];
                    })->filter()->values()->toArray(),
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

    /**
     * Post an automated announcement message from the configured system bot.
     */
    public static function sendBotMessage(string $content): ?self
    {
        $bot = User::getSystemBot();
        if (! $bot) {
            return null;
        }

        $message = static::create([
            'user_id' => $bot->id,
            'content' => $content,
        ]);

        $maxMessages = (int) AppSetting::get('global_chat_max_messages', 200);
        static::pruneOldMessages($maxMessages);

        try {
            broadcast(new ChatMessageSent($message));
        } catch (\Throwable $e) {
            // Ignore broadcast failures in testing or if pusher is offline
        }

        return $message;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'reply_to_id',
        'reply_to_content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

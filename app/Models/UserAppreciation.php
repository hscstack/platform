<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAppreciation extends Model
{
    protected $fillable = [
        'user_id',
        'appreciator_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appreciator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appreciator_id');
    }
}

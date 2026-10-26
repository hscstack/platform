<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'name',
        'sort_order',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'weight' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ChapterCompletion::class);
    }
}

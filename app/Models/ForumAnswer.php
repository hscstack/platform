<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class ForumAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'forum_post_id',
        'user_id',
        'parent_id',
        'body',
        'image_path',
        'vote_score',
        'upvotes_count',
        'downvotes_count',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'vote_score' => 'integer',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str($this->image_path)->startsWith(['http://', 'https://'])
            ? $this->image_path
            : Storage::url($this->image_path);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'forum_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumAnswer::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumAnswer::class, 'parent_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(ForumVote::class, 'voteable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}

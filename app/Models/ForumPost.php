<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForumPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'chapter_id',
        'node_id',
        'curriculum',
        'slug',
        'body',
        'images',
        'is_locked',
        'is_anonymous',
        'moderation_status',
        'vote_score',
        'upvotes_count',
        'downvotes_count',
        'answers_count',
    ];

    protected $appends = [
        'image_url',
        'image_urls',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_locked' => 'boolean',
            'is_anonymous' => 'boolean',
            'vote_score' => 'integer',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
            'answers_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getImageUrlsAttribute(): array
    {
        $images = $this->images;
        if (! is_array($images) || empty($images)) {
            return [];
        }

        return array_values(array_map(function ($img) {
            if (! $img) {
                return null;
            }

            return str($img)->startsWith(['http://', 'https://'])
                ? $img
                : Storage::url($img);
        }, array_filter($images)));
    }

    public function getImageUrlAttribute(): ?string
    {
        $urls = $this->image_urls;

        return $urls[0] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(function (ForumPost $post) {
            if ($post->moderation_status === 'approved') {
                Cache::forever('forum:latest_post', [
                    'user_id' => $post->user_id,
                    'created_at' => $post->created_at?->getTimestamp() ?? now()->timestamp,
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function node(): BelongsTo
    {
        return $this->chapter();
    }

    public function getNodeIdAttribute()
    {
        return $this->chapter_id;
    }

    public function setNodeIdAttribute($value)
    {
        $this->attributes['chapter_id'] = $value;
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ForumAnswer::class);
    }

    public function directAnswers(): HasMany
    {
        return $this->hasMany(ForumAnswer::class)->whereNull('parent_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(ForumVote::class, 'voteable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('moderation_status', 'approved');
    }

    public function isApproved(): bool
    {
        return $this->moderation_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->moderation_status === 'pending';
    }

    public function isFlagged(): bool
    {
        return $this->moderation_status === 'flagged';
    }

    public function isRejected(): bool
    {
        return $this->moderation_status === 'rejected';
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->when($filters['my_posts'] ?? null, function ($q) {
            $q->where('user_id', auth()->id());
        });

        $query->when($filters['curriculum'] ?? null, function ($q, $curriculum) {
            $q->where('curriculum', $curriculum);
        });

        $query->when($filters['subject_id'] ?? null, function ($q, $subjectId) {
            if ($subjectId === 'other') {
                $q->whereNull('subject_id');
            } else {
                $q->where('subject_id', $subjectId);
            }
        });

        $chapterId = $filters['chapter_id'] ?? $filters['node_id'] ?? null;
        $query->when($chapterId, function ($q, $cid) {
            $q->where('chapter_id', $cid);
        });

        $query->when($filters['status'] ?? null, function ($q, $status) {
            if ($status === 'unanswered') {
                $q->where('answers_count', 0);
            } elseif ($status === 'answered') {
                $q->where('answers_count', '>', 0);
            }
        });

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('body', 'like', "%{$search}%");
        });

        $sort = $filters['sort'] ?? 'recent';

        match ($sort) {
            'trending' => DB::getDriverName() === 'sqlite'
                ? $query->orderByRaw('((vote_score + (answers_count * 2) + 1) / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest()
                : $query->orderByRaw('((vote_score + (answers_count * 2) + 1) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest(),
            'top' => $query->orderByDesc('vote_score')->latest(),
            default => $query->latest(),
        };
    }

    public function snippet(int $limit = 40): string
    {
        return Str::limit(strip_tags($this->body ?? ''), $limit);
    }
}

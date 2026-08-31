<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForumPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'node_id',
        'curriculum',
        'title',
        'slug',
        'body',
        'image_path',
        'is_answered',
        'vote_score',
        'upvotes_count',
        'downvotes_count',
        'answers_count',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'is_answered' => 'boolean',
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

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str($this->image_path)->startsWith(['http://', 'https://'])
            ? $this->image_path
            : Storage::url($this->image_path);
    }

    protected static function booted(): void
    {
        static::creating(function (ForumPost $post) {
            if (empty($post->slug)) {
                $baseSlug = Str::slug($post->title) ?: 'post';
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}-".Str::lower(Str::random(4));
                    $counter++;
                }

                $post->slug = $slug;
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

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
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

    public function scopeFilter(Builder $query, array $filters): void
    {
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

        $query->when($filters['node_id'] ?? null, function ($q, $nodeId) {
            $q->where('node_id', $nodeId);
        });

        $query->when($filters['status'] ?? null, function ($q, $status) {
            if ($status === 'unanswered') {
                $q->where('is_answered', false);
            } elseif ($status === 'answered') {
                $q->where('is_answered', true);
            }
        });

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        });

        $sort = $filters['sort'] ?? 'recent';

        match ($sort) {
            'trending' => DB::getDriverName() === 'sqlite'
                ? $query->orderByRaw('(vote_score / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest()
                : $query->orderByRaw('(vote_score / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest(),
            'top' => $query->orderByDesc('vote_score')->latest(),
            default => $query->latest(),
        };
    }
}

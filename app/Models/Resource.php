<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Node $node
 */
class Resource extends Model
{
    use Prunable;

    protected $fillable = [
        'subject_id',
        'node_id',
        'user_id',
        'resource_type',
        'title',
        'description',
        'thumbnail',
        'items',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'vote_score',
        'upvotes_count',
        'downvotes_count',
    ];

    protected $appends = [
        'items_count',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'vote_score' => 'integer',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getItemsAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        $items = is_array($value) ? $value : (json_decode($value, true) ?: []);

        return array_values(array_map(function ($item) {
            $source = $item['source'] ?? '';
            $url = (str_starts_with($source, 'http://') || str_starts_with($source, 'https://'))
                ? $source
                : ($source ? Storage::url($source) : '');

            return array_merge($item, ['url' => $url]);
        }, $items));
    }

    public function getThumbnailAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://')
            ? $value
            : Storage::url($value);
    }

    public function getItemsCountAttribute(): int
    {
        return count($this->items);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get the prunable model query.
     * Prunes rejected resources older than 15 days.
     */
    public function prunable()
    {
        return static::where('status', 'rejected')
            ->where('reviewed_at', '<=', now()->subDays(15));
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ResourceVote::class);
    }

    public function upvotes(): HasMany
    {
        return $this->hasMany(ResourceVote::class)->where('value', 1);
    }

    public function downvotes(): HasMany
    {
        return $this->hasMany(ResourceVote::class)->where('value', -1);
    }

    /**
     * Top-level and nested comments associated with this resource.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ResourceComment::class);
    }
}

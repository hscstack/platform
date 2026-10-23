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

    public function setItemsAttribute($value): void
    {
        $this->attributes['items'] = is_string($value) ? $value : json_encode($value ?: []);
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

    public static function extractYoutubeThumbnail(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
        }

        return null;
    }

    public static function computeThumbnailFromItems(array $items): ?string
    {
        $first = $items[0] ?? null;
        if (! $first) {
            return null;
        }

        $format = $first['format'] ?? null;
        $source = $first['source'] ?? null;

        if ($format === 'image' && $source) {
            return $source;
        }

        if ($format === 'video' && $source) {
            return static::extractYoutubeThumbnail($source);
        }

        return null;
    }

    public function getItemsCountAttribute(): int
    {
        return count($this->items);
    }

    protected static function booted(): void
    {
        static::saving(function (Resource $resource) {
            if ($resource->isDirty('items') || empty($resource->thumbnail)) {
                $computed = static::computeThumbnailFromItems($resource->items ?? []);
                if ($computed !== null || $resource->isDirty('items')) {
                    $resource->thumbnail = $computed;
                }
            }
        });

        static::deleting(function (Resource $resource) {
            if ($resource->node?->isEffectivelyFrozen()) {
                abort(403, 'Cannot delete resources from a frozen folder.');
            }
        });
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
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
}

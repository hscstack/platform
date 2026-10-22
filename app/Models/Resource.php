<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'vote_score',
        'upvotes_count',
        'downvotes_count',
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

    protected static function booted(): void
    {
        static::deleting(function (Resource $resource) {
            if ($resource->node?->isEffectivelyFrozen()) {
                abort(403, 'Cannot delete resources from a frozen folder.');
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResourceItem::class)->oldest('id');
    }

    public function firstItem(): HasOne
    {
        return $this->hasOne(ResourceItem::class)->oldestOfMany();
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

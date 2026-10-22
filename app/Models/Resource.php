<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Node $node
 */
class Resource extends Model
{
    protected $fillable = [
        'node_id',
        'user_id',
        'resource_type',
        'title',
        'description',
    ];

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

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ResourceCompletion::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ResourceChangeRequest::class);
    }

    public function pendingChangeRequest(): HasOne
    {
        return $this->hasOne(ResourceChangeRequest::class)->where('status', 'pending');
    }

    public function latestRejectedChangeRequest(): HasOne
    {
        return $this->hasOne(ResourceChangeRequest::class)
            ->where('status', 'rejected')
            ->latestOfMany('reviewed_at');
    }

    public function hasPendingChangeRequest(): bool
    {
        return $this->relationLoaded('pendingChangeRequest')
            ? $this->pendingChangeRequest !== null
            : $this->pendingChangeRequest()->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Node $node
 */
class Resource extends Model
{
    protected $fillable = [
        'node_id',
        'resource_type',
        'title',
        'content',
        'file_path',
        'external_url',
        'user_id',
    ];

    protected $appends = [
        'file_url',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Resource $resource) {
            if ($resource->node?->isEffectivelyFrozen()) {
                abort(403, 'Cannot delete resources from a frozen folder.');
            }
        });
    }

    public function getFileUrlAttribute(): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }

        if ($this->file_path) {
            return Storage::url($this->file_path);
        }

        return null;
    }

    //
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completions()
    {
        return $this->hasMany(ResourceCompletion::class);
    }

    public function changeRequests()
    {
        return $this->hasMany(ResourceChangeRequest::class);
    }

    public function pendingChangeRequest()
    {
        return $this->hasOne(ResourceChangeRequest::class)->where('status', 'pending');
    }

    public function hasPendingChangeRequest(): bool
    {
        return $this->relationLoaded('pendingChangeRequest')
            ? $this->pendingChangeRequest !== null
            : $this->pendingChangeRequest()->exists();
    }
}

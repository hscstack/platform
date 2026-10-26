<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'name',
        'slug',
        'sort_order',
        'is_trackable',
        'weight',
        'is_frozen',
    ];

    protected function casts(): array
    {
        return [
            'is_trackable' => 'boolean',
            'weight' => 'integer',
            'is_frozen' => 'boolean',
            'resources_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Node $node) {
            if ($node->is_frozen) {
                abort(403, 'This folder is frozen and cannot be deleted.');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function completions()
    {
        return $this->hasMany(NodeCompletion::class);
    }

    public function isEffectivelyFrozen(): bool
    {
        return (bool) $this->is_frozen;
    }

    public function getIsEffectivelyFrozenAttribute(): bool
    {
        return $this->isEffectivelyFrozen();
    }
}

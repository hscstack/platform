<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property-read Node|null $parent
 */
class Node extends Model
{
    protected $fillable = [
        'user_id',
        'subject_id',
        'parent_id',
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
            'children_count' => 'integer',
            'resources_count' => 'integer',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Node $node) {
            if ($node->isEffectivelyFrozen()) {
                abort(403, 'This folder is frozen and cannot be deleted.');
            }
        });
    }

    public function isEffectivelyFrozen(): bool
    {
        if ($this->is_frozen) {
            return true;
        }

        return $this->parent ? $this->parent->isEffectivelyFrozen() : false;
    }

    public function getIsEffectivelyFrozenAttribute(): bool
    {
        return $this->isEffectivelyFrozen();
    }

    public function breadcrumb(): array
    {
        return Cache::remember("node_breadcrumb_{$this->id}", now()->addDays(7), function () {
            $breadcrumb = [];
            $node = $this;

            while ($node) {
                array_unshift($breadcrumb, [
                    'name' => $node->name,
                    'slug' => $node->slug,
                ]);

                $node = $node->parent;
            }

            return $breadcrumb;
        });
    }

    public function getBreadcrumbAttribute(): array
    {
        return $this->breadcrumb();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function children()
    {
        return $this->hasMany(Node::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Node::class, 'parent_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function votes()
    {
        return $this->hasMany(NodeVote::class);
    }

    public function upvotes()
    {
        return $this->hasMany(NodeVote::class)->where('type', 'up');
    }

    public function downvotes()
    {
        return $this->hasMany(NodeVote::class)->where('type', 'down');
    }

    public function completions()
    {
        return $this->hasMany(NodeCompletion::class);
    }

    public function changeRequests()
    {
        return $this->hasMany(ResourceChangeRequest::class);
    }

    public function pendingCreateRequests()
    {
        return $this->hasMany(ResourceChangeRequest::class)
            ->where('action_type', 'create')
            ->where('status', 'pending');
    }

    public function rejectedCreateRequests()
    {
        return $this->hasMany(ResourceChangeRequest::class)
            ->where('action_type', 'create')
            ->where('status', 'rejected');
    }
}

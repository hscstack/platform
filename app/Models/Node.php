<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read Node|null $parent
 */
class Node extends Model
{
    protected $fillable = [
        'subject_id',
        'parent_id',
        'name',
        'slug',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'children_count' => 'integer',
            'resources_count' => 'integer',
        ];
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
}

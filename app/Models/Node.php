<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\Node|null $parent
 */

class Node extends Model
{
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

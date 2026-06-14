<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\Node $node
 */
class Resource extends Model
{
    //
    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $guarded = [];
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }
}

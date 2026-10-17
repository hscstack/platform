<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_trackable' => 'boolean',
            'is_optional' => 'boolean',
            'is_disabled' => 'boolean',
        ];
    }

    public function nodes()
    {
        return $this->hasMany(Node::class);
    }
}

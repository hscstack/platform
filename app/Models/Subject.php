<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'course',
        'group',
        'sort_order',
        'is_disabled',
        'is_optional',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_disabled' => 'boolean',
            'is_optional' => 'boolean',
        ];
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function nodes(): HasMany
    {
        return $this->chapters();
    }
}

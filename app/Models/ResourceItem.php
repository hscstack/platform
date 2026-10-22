<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ResourceItem extends Model
{
    protected $fillable = [
        'resource_id',
        'format',
        'title',
        'source',
    ];

    protected $appends = [
        'url',
    ];

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->source, 'http://') || str_starts_with($this->source, 'https://')) {
            return $this->source;
        }

        return Storage::url($this->source);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}

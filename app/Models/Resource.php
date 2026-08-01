<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read \App\Models\Node $node
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
}

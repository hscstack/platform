<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Blog extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'is_published',
        'is_featured',
        'meta_title',
        'meta_description',
        'seo_tags',
        'views',
        'featured_image_path',
    ];

    protected $appends = [
        'featured_image',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'views' => 'integer',
    ];

    public function getFeaturedImageAttribute(): ?string
    {
        if (! $this->featured_image_path) {
            return null;
        }

        return str($this->featured_image_path)->startsWith(['http://', 'https://'])
            ? $this->featured_image_path
            : Storage::url($this->featured_image_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(BlogReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

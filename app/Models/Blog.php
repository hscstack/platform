<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $this->featured_image_path
            ? Storage::url($this->featured_image_path)
            : null;
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

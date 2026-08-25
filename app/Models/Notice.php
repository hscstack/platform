<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Notice extends Model
{
    protected $casts = ['show_button' => 'boolean', 'is_active' => 'boolean'];

    protected $guarded = [];

    public function getImageAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return str($value)->startsWith(['http://', 'https://'])
            ? $value
            : Storage::url($value);
    }

    public static function singleton(): self
    {
        return static::firstOrCreate([], [
            'title' => null,
            'message' => null,
            'image' => null,
            'show_button' => false,
            'button_title' => null,
            'button_link' => null,
            'is_active' => false,
        ]);
    }

    public static function activeForDisplay(): ?self
    {
        return static::where('is_active', true)->first();
    }
}

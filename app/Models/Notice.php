<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $casts = ['show_button' => 'boolean', 'is_active' => 'boolean'];

    protected $guarded = [];

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

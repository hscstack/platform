<?php

namespace App\Observers;

use App\Models\Blog;
use Illuminate\Support\Facades\Cache;

class BlogObserver
{
    public function saved(Blog $blog): void
    {
        if (
            $blog->is_featured ||
            $blog->getOriginal('is_featured') ||
            $blog->wasChanged('is_published')
        ) {
            Cache::forget('home_page_data');
        }
    }

    public function deleted(Blog $blog): void
    {
        if ($blog->is_featured) {
            Cache::forget('home_page_data');
        }
    }
}

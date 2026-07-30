<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
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
            CacheHelper::clearHomePage();
        }
    }

    public function deleted(Blog $blog): void
    {
        if ($blog->is_featured) {
            CacheHelper::clearHomePage();
        }
    }
}

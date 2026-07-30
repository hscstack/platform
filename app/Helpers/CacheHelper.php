<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function clearHomePage(?string $course = null): void
    {
        if ($course) {
            Cache::forget("home_page_data_{$course}");
            return;
        }

        Cache::forget('home_page_data_hsc');
        Cache::forget('home_page_data_ssc');
    }
}

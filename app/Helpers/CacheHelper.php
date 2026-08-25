<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function clearHomePage(?string $course = null): void
    {
        if ($course) {
            Cache::forget("home_page_subjects_{$course}");

            return;
        }

        Cache::forget('home_page_subjects_hsc');
        Cache::forget('home_page_subjects_ssc');
    }
}

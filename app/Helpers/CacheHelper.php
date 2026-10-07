<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function clearHomePage(?string $course = null): void
    {
        $courses = $course ? [$course] : ['hsc', 'ssc'];
        $groups = ['science', 'humanities', 'commerce'];

        foreach ($courses as $c) {
            Cache::forget("home_page_subjects_{$c}");
            foreach ($groups as $g) {
                Cache::forget("home_page_subjects_{$c}_{$g}");
            }
        }

        if (! $course) {
            Cache::forget('home_page_featured_blogs');
            Cache::forget('home_page_trending_posts');
            Cache::forget('home_page_notice');
            Cache::forget('forum_filter_subjects');
        }
    }
}

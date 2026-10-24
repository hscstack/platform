<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function clearHomePage(?string $course = null): void
    {
        if (! $course) {
            Cache::forget('forum_filter_subjects');
        }

        self::clearCurriculumSubjects();
    }

    public static function clearCurriculumSubjects(): void
    {
        foreach (['hsc', 'ssc'] as $course) {
            foreach (['science', 'humanities', 'commerce'] as $group) {
                Cache::forget("curriculum_subjects_{$course}_{$group}");
            }
        }
    }
}

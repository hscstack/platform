<?php

namespace App\Observers;

use App\Models\Chapter;
use Illuminate\Support\Facades\Cache;

class ChapterObserver
{
    public function created(Chapter $chapter): void
    {
        $this->clearChapterCache($chapter);
    }

    public function updated(Chapter $chapter): void
    {
        $this->clearChapterCache($chapter);
    }

    public function deleted(Chapter $chapter): void
    {
        $this->clearChapterCache($chapter);
    }

    private function clearChapterCache(Chapter $chapter): void
    {
        Cache::forget('home:counts');
        Cache::forget('home:subjects_with_chapters');
        Cache::forget('notes:subjects_with_chapters');
        Cache::forget('notes:resources_list');
        Cache::forget('forum_filter_subjects');

        if ($chapter->subject_id) {
            Cache::forget("tracker:subject_chapters:{$chapter->subject_id}");
        }
    }
}

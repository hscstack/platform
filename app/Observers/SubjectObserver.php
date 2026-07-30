<?php

namespace App\Observers;

use App\Models\Subject;
use Illuminate\Support\Facades\Cache;

class SubjectObserver
{
    public function saved(Subject $subject): void
    {
        Cache::forget('home_page_data');
    }

    public function deleted(Subject $subject): void
    {
        Cache::forget('home_page_data');
    }
}

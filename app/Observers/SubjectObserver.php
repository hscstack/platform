<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;

class SubjectObserver
{
    public function saved(Subject $subject): void
    {
        CacheHelper::clearHomePage($subject->course);
    }

    public function deleted(Subject $subject): void
    {
        CacheHelper::clearHomePage($subject->course);
    }
}

<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Subject;

class SubjectObserver
{
    public function saved(Subject $subject): void
    {
        CacheHelper::clearHomePage();
    }

    public function deleted(Subject $subject): void
    {
        CacheHelper::clearHomePage();
    }
}

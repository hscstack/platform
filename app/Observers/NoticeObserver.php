<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Notice;
use Illuminate\Support\Facades\Cache;

class NoticeObserver
{
    public function saved(Notice $notice): void
    {
        Cache::forget('active_notice_for_display');
        CacheHelper::clearHomePage();
    }

    public function deleted(Notice $notice): void
    {
        Cache::forget('active_notice_for_display');
        CacheHelper::clearHomePage();
    }
}

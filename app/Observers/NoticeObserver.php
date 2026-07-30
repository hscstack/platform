<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Notice;
use Illuminate\Support\Facades\Cache;

class NoticeObserver
{
    public function saved(Notice $notice): void
    {
        CacheHelper::clearHomePage();
    }
}

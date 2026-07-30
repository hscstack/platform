<?php

namespace App\Observers;

use App\Models\Notice;
use Illuminate\Support\Facades\Cache;

class NoticeObserver
{
    public function saved(Notice $notice): void
    {
        Cache::forget('home_page_data');
    }
}

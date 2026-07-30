<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function saved(User $user): void
    {
        Cache::forget('about_us_info');
    }

    public function deleted(User $user): void
    {
        Cache::forget('about_us_info');
    }
}

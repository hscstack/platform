<?php

namespace App\Observers;

use App\Models\Node;
use Illuminate\Support\Facades\Cache;

class NodeObserver
{
    public function created(Node $node): void
    {
        if ($node->parent_id === null) {
            Cache::forget('home_page_data');
        }
    }

    public function deleted(Node $node): void
    {
        if ($node->parent_id === null) {
            Cache::forget('home_page_data');
        }
    }
}

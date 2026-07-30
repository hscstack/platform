<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Node;
use Illuminate\Support\Facades\Cache;

class NodeObserver
{
    public function created(Node $node): void
    {
        if ($node->parent_id === null) {
            CacheHelper::clearHomePage();
        }
    }

    public function deleted(Node $node): void
    {
        if ($node->parent_id === null) {
            CacheHelper::clearHomePage();
        }
    }
}

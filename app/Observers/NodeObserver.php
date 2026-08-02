<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Node;
use Illuminate\Support\Facades\Cache;

class NodeObserver
{
    public function created(Node $node): void
    {
        $this->clearNodeCache($node);
    }

    public function updated(Node $node): void
    {
        $this->clearNodeCache($node);
    }

    public function deleted(Node $node): void
    {
        $this->clearNodeCache($node);
    }

    private function clearNodeCache(Node $node): void
    {
        Cache::forget("node_children_{$node->id}");
        Cache::forget("node_resources_{$node->id}");
        Cache::forget("node_breadcrumb_{$node->id}");

        if ($node->parent_id) {
            Cache::forget("node_children_{$node->parent_id}");
        } else {
            Cache::forget("subject_page_{$node->subject_id}");
        }

        if ($node->parent_id === null) {
            CacheHelper::clearHomePage();
        }
    }
}

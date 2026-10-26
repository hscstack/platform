<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Node;

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
        CacheHelper::clearHomePage();
    }
}

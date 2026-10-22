<?php

namespace App\Observers;

use App\Models\Resource;
use Illuminate\Support\Facades\Cache;

class ResourceObserver
{
    public function saved(Resource $resource): void
    {
        if ($resource->status === 'approved' || $resource->wasChanged('status')) {
            $this->clearCache($resource);
        }
    }

    public function deleted(Resource $resource): void
    {
        if ($resource->status === 'approved') {
            $this->clearCache($resource);
        }
    }

    private function clearCache(Resource $resource): void
    {
        Cache::forget("resource_{$resource->id}");
        Cache::forget("node_resources_{$resource->node_id}");

        $ids = Resource::where('node_id', $resource->node_id)
            ->pluck('id');

        foreach ($ids as $id) {
            Cache::forget("resource_{$id}");
        }
    }
}

<?php

namespace App\Observers;

use App\Models\Node;
use App\Models\Resource;
use Illuminate\Support\Facades\Cache;

class ResourceObserver
{
    public function creating(Resource $resource): void
    {
        if ($resource->node_id && $resource->user_id) {
            $otherResourcesCount = Resource::where('node_id', $resource->node_id)->count();

            if ($otherResourcesCount === 0) {
                $node = Node::find($resource->node_id);
                if ($node && $node->user_id !== $resource->user_id) {
                    $node->updateQuietly(['user_id' => $resource->user_id]);
                }
            }
        }
    }

    public function saved(Resource $resource): void
    {
        $this->clearCache($resource);
    }

    public function deleted(Resource $resource): void
    {
        $this->clearCache($resource);
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

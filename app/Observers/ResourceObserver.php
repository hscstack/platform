<?php

namespace App\Observers;

use App\Models\Resource;
use Illuminate\Support\Facades\Cache;

class ResourceObserver
{
    public function creating(Resource $resource): void
    {
        if (empty($resource->subject_id) && ! empty($resource->node_id)) {
            $resource->subject_id = $resource->node?->subject_id;
        }
    }

    public function saved(Resource $resource): void
    {
        Cache::forget("resource_public_{$resource->id}");
    }

    public function deleted(Resource $resource): void
    {
        Cache::forget("resource_public_{$resource->id}");
    }
}

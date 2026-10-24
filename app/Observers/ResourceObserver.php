<?php

namespace App\Observers;

use App\Models\Resource;
use Illuminate\Support\Facades\Cache;

class ResourceObserver
{
    public function saved(Resource $resource): void
    {
        Cache::forget("resource_public_{$resource->id}");
    }

    public function deleted(Resource $resource): void
    {
        Cache::forget("resource_public_{$resource->id}");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ResourceController extends Controller
{
    //

    public function show($id)
    {
        $data = Cache::rememberForever("resource_{$id}", function () use ($id) {
            $resource = Resource::with('user')->findOrFail($id);

            $previousResourceId = Resource::where('node_id', $resource->node_id)
                ->where('id', '<', $resource->id)
                ->orderByDesc('id')
                ->value('id');

            $nextResourceId = Resource::where('node_id', $resource->node_id)
                ->where('id', '>', $resource->id)
                ->orderBy('id')
                ->value('id');

            return [
                'resource' => $resource->toArray(),
                'previousResourceId' => $previousResourceId,
                'nextResourceId' => $nextResourceId,
            ];
        });

        return Inertia::render('Resource', $data);
    }
}

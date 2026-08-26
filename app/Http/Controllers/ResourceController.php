<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceCompletion;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ResourceController extends Controller
{
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

        $isCompleted = auth()->check()
            ? ResourceCompletion::where('resource_id', $id)->where('user_id', auth()->id())->exists()
            : false;

        $completionsCount = ResourceCompletion::where('resource_id', $id)->count();

        $completers = ResourceCompletion::where('resource_id', $id)
            ->with('user:id,name,image_path,institution')
            ->latest()
            ->take(10)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        return Inertia::render('Resource', array_merge($data, [
            'isCompleted' => $isCompleted,
            'completionsCount' => $completionsCount,
            'completers' => $completers,
        ]));
    }

    public function toggleComplete(Resource $resource)
    {
        $user = auth()->user();
        $existing = $resource->completions()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $resource->completions()->create(['user_id' => $user->id]);
        }

        return back();
    }
}

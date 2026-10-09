<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceCompletion;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function show($id)
    {
        $resource = Resource::with(['user', 'node.subject'])->findOrFail($id);

        if ($resource->status !== 'approved') {
            $user = auth()->user();
            if (! $user || ($user->id !== $resource->user_id && ! $user->can('approve resources'))) {
                abort(404);
            }
        }

        $loadData = function () use ($resource) {
            $previousResourceId = Resource::where('node_id', $resource->node_id)
                ->where('status', 'approved')
                ->where('id', '<', $resource->id)
                ->orderByDesc('id')
                ->value('id');

            $nextResourceId = Resource::where('node_id', $resource->node_id)
                ->where('status', 'approved')
                ->where('id', '>', $resource->id)
                ->orderBy('id')
                ->value('id');

            return [
                'resource' => $resource->toArray(),
                'subject' => $resource->node?->subject?->toArray(),
                'previousResourceId' => $previousResourceId,
                'nextResourceId' => $nextResourceId,
            ];
        };

        $data = $resource->status === 'approved'
            ? Cache::rememberForever("resource_{$id}", $loadData)
            : $loadData();

        $isCompleted = auth()->check()
            ? ResourceCompletion::where('resource_id', $id)->where('user_id', auth()->id())->exists()
            : false;

        $completionsCount = ResourceCompletion::where('resource_id', $id)->count();

        $completers = ResourceCompletion::where('resource_id', $id)
            ->with(['user:id,name,username,image_path,institution,is_verified'])
            ->latest()
            ->take(10)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        $node = Node::find($data['resource']['node_id']);

        return Inertia::render('Resource', array_merge($data, [
            'breadcrumb' => $node ? $node->breadcrumb() : [],
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

<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NodeController extends Controller
{
    public function show(Subject $subject, $path)
    {
        if ($subject->is_disabled) {
            abort(404);
        }

        $slugs = explode('/', trim($path, '/'));

        $node = null;
        $parent = null;

        foreach ($slugs as $slug) {
            $query = Node::where('subject_id', $subject->id)
                ->where('slug', $slug);

            if ($parent) {
                $query->where('parent_id', $parent->id);
            } else {
                $query->whereNull('parent_id');
            }

            $node = $query->firstOrFail();
            $parent = $node;
        }

        $nodes = Cache::remember("node_children_{$node->id}", now()->addDay(), function () use ($node) {
            return Node::where('parent_id', $node->id)
                ->withCount(['children', 'resources' => function ($q) {
                    $q->where('status', 'approved');
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])->toArray();
        });

        $resources = [];

        if (empty($nodes)) {
            $resources = Cache::remember("node_resources_{$node->id}", now()->addDay(), function () use ($node) {
                return $node->resources()
                    ->where('status', 'approved')
                    ->withCount('items')
                    ->with('user:id,name,username')
                    ->orderByDesc('vote_score')
                    ->latest('id')
                    ->get()
                    ->toArray();
            });

            if ($userId = auth()->id()) {
                $unapproved = $node->resources()
                    ->where('user_id', $userId)
                    ->whereIn('status', ['pending', 'rejected'])
                    ->withCount('items')
                    ->with('user:id,name,username')
                    ->latest('id')
                    ->get()
                    ->toArray();

                if (! empty($unapproved)) {
                    $resources = array_merge($unapproved, $resources);
                }
            }
        }

        return Inertia::render('Node', [
            'subject' => $subject,
            'currentNode' => [
                'id' => $node->id,
                'name' => $node->name,
                'slug' => $node->slug,
                'is_frozen' => $node->isEffectivelyFrozen(),
            ],
            'nodes' => $nodes,
            'breadcrumb' => $node->breadcrumb(),
            'resources' => $resources,
        ]);
    }
}

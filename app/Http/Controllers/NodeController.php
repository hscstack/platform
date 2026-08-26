<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NodeController extends Controller
{
    public function show(Subject $subject, $path)
    {
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
                ->withCount(['children', 'resources', 'upvotes', 'downvotes'])
                ->orderByRaw('(upvotes_count - downvotes_count) DESC')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug'])->toArray();
        });

        $resources = Cache::remember("node_resources_{$node->id}", now()->addDay(), function () use ($node) {
            return $node->resources()->get()->toArray();
        });

        $upvotesCount = $node->upvotes()->count();
        $downvotesCount = $node->downvotes()->count();
        $userVote = auth()->check()
            ? $node->votes()->where('user_id', auth()->id())->value('type')
            : null;

        $upvoters = $node->upvotes()
            ->with(['user:id,name,username,image_path,institution', 'user.roles:id,name'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        return Inertia::render('Node', [
            'subject' => $subject,
            'currentNode' => [
                'id' => $node->id,
                'name' => $node->name,
                'slug' => $node->slug,
            ],
            'nodes' => $nodes,
            'breadcrumb' => Cache::remember("node_breadcrumb_{$node->id}", now()->addDay(), function () use ($node) {
                return $this->buildBreadcrumb($node);
            }),
            'resources' => $resources,
            'upvotesCount' => $upvotesCount,
            'downvotesCount' => $downvotesCount,
            'userVote' => $userVote,
            'upvoters' => $upvoters,
        ]);
    }

    public function vote(Request $request, Node $node)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:up,down'],
        ]);

        $userId = auth()->id();
        $existing = $node->votes()->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->type === $validated['type']) {
                $existing->delete();
            } else {
                $existing->update(['type' => $validated['type']]);
            }
        } else {
            $node->votes()->create([
                'user_id' => $userId,
                'type' => $validated['type'],
            ]);
        }

        return back();
    }

    private function buildBreadcrumb($node)
    {
        $breadcrumb = [];

        while ($node) {
            array_unshift($breadcrumb, [
                'name' => $node->name,
                'slug' => $node->slug,
            ]);

            $node = $node->parent;
        }

        return $breadcrumb;
    }
}

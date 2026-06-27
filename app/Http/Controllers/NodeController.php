<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NodeController extends Controller
{
    //
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

        $nodes = Cache::rememberForever("node_children_{$node->id}", function () use ($node) {
            return Node::where('parent_id', $node->id)
                ->orderBy('sort_order')
                ->withCount(['children', 'resources'])
                ->get(['id', 'name', 'slug'])->toArray();
        });

        $resources = Cache::rememberForever("node_resources_{$node->id}", function () use ($node) {
            return $node->resources()->get()->toArray();
        });

        return Inertia::render('Node', [
            'subject' => $subject,
            'nodes' => $nodes,
            'breadcrumb' => Cache::rememberForever("node_breadcrumb_{$node->id}", function () use ($node) {
                return $this->buildBreadcrumb($node);
            }),
            'resources' => $resources,
        ]);
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

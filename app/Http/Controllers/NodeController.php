<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use Illuminate\Http\Request;
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

        $nodes = Node::where('parent_id', $node->id)
            ->orderBy('sort_order')
            ->withCount(['children', 'resources'])
            ->get(['id', 'name', 'slug']);

        $resources = $node->resources()->get();
        return Inertia::render('Node', [
            'subject' => $subject,
            'nodes' => $nodes,
            'breadcrumb' => $this->buildBreadcrumb($node),
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

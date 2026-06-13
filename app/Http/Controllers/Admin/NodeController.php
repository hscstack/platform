<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NodeController extends Controller
{

    public function show(Subject $subject, $path = null)
    {
        if (!$path) {
            $nodes = Node::where('subject_id', $subject->id)
                ->orderBy('sort_order')
                ->whereNull('parent_id')
                ->get();

            return Inertia::render('admin/Node', [
                'subject' => $subject,
                'nodes' => $nodes,
                'resources' => [],

            ]);
        }

        $slugs = explode('/', trim($path, '/'));

        $node = Node::where('subject_id', $subject->id)
            ->orderBy('sort_order')
            ->whereNull('parent_id')
            ->where('slug', $slugs[0])
            ->first();

        foreach (array_slice($slugs, 1) as $slug) {
            $node = $node->children()->where('slug', $slug)->first();
        }

        return Inertia::render('admin/Node', [
            'subject' => $subject,
            'nodes' => $node->children,
            'resources' => $node->resources ?? [],
            'parent' => $node ?? null,
        ]);
    }


    public function create(Subject $subject, Request $request)
    {
        $parent = null;

        if ($request->parent_id) {
            $parent = Node::where('id', $request->parent_id)
                ->where('subject_id', $subject->id)
                ->firstOrFail();
        }

        return Inertia::render('admin/NodeCreate', [
            'subject' => $subject,
            'parent' => $parent
        ]);
    }


    public function store(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200', 'min:2'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $parent = null;

        if (!empty($validated['parent_id'])) {
            $parent = Node::where('id', $validated['parent_id'])
                ->where('subject_id', $subject->id)
                ->firstOrFail();
        }

        $node = new Node();

        $node->subject_id = $subject->id;
        $node->parent_id = $parent?->id;
        $node->name = $validated['name'];
        $node->slug = Str::slug($validated['name']);
        $node->sort_order = $validated['sort_order'] ?? 0;

        $node->save();

        $redirect = str_replace('/create', '', url()->previous());

        return redirect($redirect)->with('success', 'Node created successfully.');
    }
}

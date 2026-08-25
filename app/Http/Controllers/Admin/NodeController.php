<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Node\StoreNodeRequest;
use App\Http\Requests\Node\UpdateNodeRequest;
use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NodeController extends Controller
{
    public function show(Subject $subject, $path = null)
    {
        if (! $path) {
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
        if (! $node) {
            abort(404);
        }

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

        return Inertia::render('admin/NodeCreateOrEdit', [
            'subject' => $subject,
            'parent' => $parent,
            'redirect' => url()->previous(),
        ]);
    }

    public function edit(Node $node)
    {

        return Inertia::render('admin/NodeCreateOrEdit', [
            'subject' => $node->subject,
            'node' => $node,
            'parent' => $node->parent,
            'redirect' => url()->previous(),
        ]);
    }

    public function store(StoreNodeRequest $request, Subject $subject)
    {
        $validated = $request->validated();

        $parent = null;

        if (! empty($validated['parent_id'])) {
            $parent = Node::where('id', $validated['parent_id'])
                ->where('subject_id', $subject->id)
                ->firstOrFail();
        }

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $exists = Node::where('subject_id', $subject->id)
            ->where('parent_id', $parent?->id)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'slug' => 'Folder with this slug already exists in this location.',
                ])
                ->withInput();
        }

        Node::create([
            'subject_id' => $subject->id,
            'parent_id' => $parent?->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $redirect = $validated['redirect'] ? $validated['redirect'] : explode('/create', url()->previous())[0];

        return redirect($redirect)->with('success', 'Node created successfully.');
    }

    public function update(UpdateNodeRequest $request, Subject $subject, Node $node)
    {
        $validated = $request->validated();

        if (array_key_exists('parent_id', $validated)) {
            $parent = null;

            if (! empty($validated['parent_id'])) {
                $parent = Node::where('id', $validated['parent_id'])
                    ->where('subject_id', $subject->id)
                    ->where('id', '!=', $node->id)
                    ->firstOrFail();
            }

            $node->parent_id = $parent?->id;
        }

        if (array_key_exists('name', $validated)) {
            $node->name = $validated['name'];
        }

        if (array_key_exists('name', $validated) || array_key_exists('slug', $validated) || array_key_exists('parent_id', $validated)) {
            $rawSlug = ! empty($validated['slug'])
                ? $validated['slug']
                : ($validated['name'] ?? $node->name);

            $slug = Str::slug($rawSlug);

            $parentId = array_key_exists('parent_id', $validated)
                ? $validated['parent_id']
                : $node->parent_id;

            $exists = Node::where('subject_id', $subject->id)
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->where('id', '!=', $node->id)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'slug' => 'Folder with this slug already exists in this location.',
                ])->withInput();
            }

            $node->slug = $slug;
        }

        if (array_key_exists('sort_order', $validated)) {
            $node->sort_order = $validated['sort_order'] ?? 0;
        }

        $node->save();

        $redirect = $validated['redirect'] ?? explode('/edit', url()->previous())[0];

        return redirect($redirect)->with('success', 'Node updated successfully.');
    }

    public function destroy(Node $node)
    {
        $node->delete();

        return redirect()->back()->with('success', 'Node deleted successfully.');
    }
}

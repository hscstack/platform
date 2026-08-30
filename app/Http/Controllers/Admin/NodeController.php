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
            'user_id' => auth()->id(),
            'subject_id' => $subject->id,
            'parent_id' => $parent?->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Folder created successfully.');
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

            $exists = Node::where('subject_id', $subject->id)
                ->where('parent_id', $node->parent_id)
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

        return back()->with('success', 'Folder updated successfully.');
    }

    public function batchStore(Request $request, Subject $subject)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:nodes,id',
            'nodes' => 'required|array|min:1',
            'nodes.*.name' => 'required|string|max:255',
            'nodes.*.slug' => 'nullable|string|max:255',
            'nodes.*.children' => 'nullable|array',
        ]);

        $parentId = $request->input('parent_id');
        $parent = null;
        if ($parentId) {
            $parent = Node::where('id', $parentId)->where('subject_id', $subject->id)->firstOrFail();
        }

        $inputNodes = $request->input('nodes');

        // 1. Check duplicate slugs among incoming root items
        $incomingRootSlugs = [];
        foreach ($inputNodes as $nodeData) {
            $nodeName = trim($nodeData['name'] ?? '');
            if (empty($nodeName)) {
                continue;
            }
            $rawSlug = ! empty($nodeData['slug']) ? $nodeData['slug'] : $nodeName;
            $slug = Str::slug($rawSlug);

            if (in_array($slug, $incomingRootSlugs, true)) {
                return back()->withErrors([
                    'slug' => "Duplicate slug '{$slug}' found in your submitted chapter list.",
                ])->withInput();
            }
            $incomingRootSlugs[] = $slug;

            // Check if slug already exists in database for this subject and parent
            $exists = Node::where('subject_id', $subject->id)
                ->where('parent_id', $parent?->id)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'slug' => "Folder with slug '{$slug}' already exists in this location.",
                ])->withInput();
            }

            // Check duplicate child slugs within this chapter
            if (! empty($nodeData['children']) && is_array($nodeData['children'])) {
                $incomingChildSlugs = [];
                foreach ($nodeData['children'] as $childItem) {
                    $childName = is_array($childItem) ? ($childItem['name'] ?? '') : (string) $childItem;
                    $childName = trim($childName);
                    if (empty($childName)) {
                        continue;
                    }
                    $childRawSlug = is_array($childItem) && ! empty($childItem['slug']) ? $childItem['slug'] : $childName;
                    $childSlug = Str::slug($childRawSlug);

                    if (in_array($childSlug, $incomingChildSlugs, true)) {
                        return back()->withErrors([
                            'slug' => "Duplicate sub-folder slug '{$childSlug}' found under chapter '{$nodeName}'.",
                        ])->withInput();
                    }
                    $incomingChildSlugs[] = $childSlug;
                }
            }
        }

        $baseSortOrder = Node::where('subject_id', $subject->id)
            ->where('parent_id', $parent?->id)
            ->max('sort_order') ?? 0;

        $createdCount = 0;

        \DB::transaction(function () use ($inputNodes, $subject, $parent, $baseSortOrder, &$createdCount) {
            foreach ($inputNodes as $index => $nodeData) {
                $nodeName = trim($nodeData['name']);
                if (empty($nodeName)) {
                    continue;
                }

                $rawSlug = ! empty($nodeData['slug']) ? $nodeData['slug'] : $nodeName;
                $slug = Str::slug($rawSlug);

                $node = Node::create([
                    'user_id' => auth()->id(),
                    'subject_id' => $subject->id,
                    'parent_id' => $parent?->id,
                    'name' => $nodeName,
                    'slug' => $slug,
                    'sort_order' => $baseSortOrder + $index + 1,
                ]);

                $createdCount++;

                // Create children if any
                if (! empty($nodeData['children']) && is_array($nodeData['children'])) {
                    foreach ($nodeData['children'] as $childIndex => $childItem) {
                        $childName = is_array($childItem) ? ($childItem['name'] ?? '') : (string) $childItem;
                        $childName = trim($childName);
                        if (empty($childName)) {
                            continue;
                        }

                        $childRawSlug = is_array($childItem) && ! empty($childItem['slug']) ? $childItem['slug'] : $childName;
                        $childSlug = Str::slug($childRawSlug);

                        Node::create([
                            'user_id' => auth()->id(),
                            'subject_id' => $subject->id,
                            'parent_id' => $node->id,
                            'name' => $childName,
                            'slug' => $childSlug,
                            'sort_order' => $childIndex,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', "Successfully generated {$createdCount} folders with subfolders!");
    }

    public function destroy(Node $node)
    {
        $node->delete();

        return redirect()->back()->with('success', 'Node deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Node\StoreNodeRequest;
use App\Http\Requests\Node\UpdateNodeRequest;
use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NodeController extends Controller
{
    public function show(Subject $subject)
    {
        $nodes = Node::where('subject_id', $subject->id)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('admin/Node', [
            'subject' => $subject,
            'nodes' => $nodes,
            'resources' => [],
            'breadcrumb' => [],
        ]);
    }

    public function store(StoreNodeRequest $request, Subject $subject)
    {
        $validated = $request->validated();

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $exists = Node::where('subject_id', $subject->id)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'slug' => 'Chapter with this slug already exists for this subject.',
                ])
                ->withInput();
        }

        Node::create([
            'user_id' => auth()->id(),
            'subject_id' => $subject->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_trackable' => (bool) ($validated['is_trackable'] ?? false),
            'weight' => (int) ($validated['weight'] ?? 2),
        ]);

        return back()->with('success', 'Chapter created successfully.');
    }

    public function update(UpdateNodeRequest $request, Subject $subject, Node $node)
    {
        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $node->name = $validated['name'];
        }

        if (array_key_exists('name', $validated) || array_key_exists('slug', $validated)) {
            $rawSlug = ! empty($validated['slug'])
                ? $validated['slug']
                : ($validated['name'] ?? $node->name);

            $slug = Str::slug($rawSlug);

            $exists = Node::where('subject_id', $subject->id)
                ->where('slug', $slug)
                ->where('id', '!=', $node->id)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'slug' => 'Chapter with this slug already exists for this subject.',
                ])->withInput();
            }

            $node->slug = $slug;
        }

        if (array_key_exists('sort_order', $validated)) {
            $node->sort_order = $validated['sort_order'] ?? 0;
        }

        if (array_key_exists('is_trackable', $validated)) {
            $node->is_trackable = (bool) $validated['is_trackable'];
        }

        if (array_key_exists('weight', $validated)) {
            $node->weight = (int) $validated['weight'];
        }

        $node->save();

        return back()->with('success', 'Chapter updated successfully.');
    }

    public function batchStore(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'nodes' => 'required|array|min:1',
            'nodes.*.name' => 'required|string|max:255',
            'nodes.*.slug' => 'nullable|string|max:255',
            'should_track_top_folders' => 'nullable|boolean',
        ]);

        $shouldTrackTopFolders = (bool) ($validated['should_track_top_folders'] ?? false);
        $inputNodes = $request->input('nodes');

        // Check duplicate slugs among incoming items and against database
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

            $exists = Node::where('subject_id', $subject->id)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'slug' => "Chapter with slug '{$slug}' already exists in this subject.",
                ])->withInput();
            }
        }

        $baseSortOrder = Node::where('subject_id', $subject->id)
            ->max('sort_order') ?? 0;

        $createdCount = 0;

        DB::transaction(function () use ($inputNodes, $subject, $baseSortOrder, $shouldTrackTopFolders, &$createdCount) {
            foreach ($inputNodes as $index => $nodeData) {
                $nodeName = trim($nodeData['name']);
                if (empty($nodeName)) {
                    continue;
                }

                $rawSlug = ! empty($nodeData['slug']) ? $nodeData['slug'] : $nodeName;
                $slug = Str::slug($rawSlug);

                Node::create([
                    'user_id' => auth()->id(),
                    'subject_id' => $subject->id,
                    'name' => $nodeName,
                    'slug' => $slug,
                    'sort_order' => $baseSortOrder + $index + 1,
                    'is_trackable' => $shouldTrackTopFolders,
                ]);

                $createdCount++;
            }
        });

        return back()->with('success', "Successfully generated {$createdCount} chapters!");
    }

    public function destroy(Node $node)
    {
        $node->delete();

        return redirect()->back()->with('success', 'Chapter deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResourceController extends Controller
{

    public function create(Request $request)
    {
        $node = Node::findOrFail($request->node_id);


        return Inertia::render('admin/ResourceCreateOrEdit', [
            'redirect' => url()->previous(),
            'node' => $node
        ]);
    }
    public function edit(Resource $resource)
    {
        $node = $resource->node;


        return Inertia::render('admin/ResourceCreateOrEdit', [
            'redirect' =>  url()->previous(),
            'node' => $node,
            'resource' => $resource,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'redirect'      => ['nullable', 'string'],
            'node_id'       => ['required', 'integer', 'exists:nodes,id'],
            'resource_type' => ['required', 'in:note,question,pdf,image,video'],
            'title'         => ['required', 'string', 'max:100', 'min:2'],
            'content'       => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10000', 'mimes:pdf,jpg,jpeg,png,mp4'],
        ]);

        $fileUrl = null;

        if ($request->hasFile('file')) {
            $path    = $request->file('file')->store("resources/{$validated['resource_type']}s", 'public');
            $fileUrl = Storage::url($path);
        }

        $resource = new Resource();
        $resource->node_id       = $validated['node_id'];
        $resource->resource_type = $validated['resource_type'];
        $resource->title         = $validated['title'];
        $resource->content = $validated['content'] ?? null;
        $resource->file_url      = $fileUrl;
        $resource->save();

        $redirect = $validated['redirect'] ?? explode('/resources', url()->previous())[0];

        return redirect($redirect)->with('success', 'Resource created successfully.');
    }


    public function update(Request $request, Resource $resource)
    {

        $validated = $request->validate([
            'redirect'      => ['nullable', 'string'],
            'node_id'       => ['sometimes', 'integer', 'exists:nodes,id'],
            'resource_type' => ['sometimes', 'in:note,question,pdf,image,video'],
            'title'         => ['sometimes', 'string', 'max:100', 'min:2'],
            'content'       => ['sometimes', 'nullable', 'string'],
            'file'          => ['sometimes', 'nullable', 'file', 'max:10000', 'mimes:pdf,jpg,jpeg,png,mp4'],
        ]);

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($resource->file_url) {
                $oldPath = str_replace('/storage/', '', parse_url($resource->file_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $type    = $validated['resource_type'] ?? $resource->resource_type;
            $path    = $request->file('file')->store("resources/{$type}s", 'public');
            $resource->file_url = Storage::url($path);
        }

        if (isset($validated['node_id']))       $resource->node_id       = $validated['node_id'];
        if (isset($validated['resource_type'])) $resource->resource_type = $validated['resource_type'];
        if (isset($validated['title']))         $resource->title         = $validated['title'];

        if (array_key_exists('content', $validated)) {
            $resource->content = $validated['content'];
        }

        $resource->save();
        $redirect = $validated['redirect'] ?? "/admin/subjects";

        return redirect($redirect)->with('success', 'Resource updated successfully.');
    }

    public function destroy(Resource $resource)
    {
        if ($resource->file_url) {
            $oldPath = str_replace('/storage/', '', parse_url($resource->file_url, PHP_URL_PATH));
            Storage::disk('public')->delete($oldPath);
        }

        $resource->delete();

        return redirect()->back()->with('success', 'Resource deleted successfully.');
    }
}

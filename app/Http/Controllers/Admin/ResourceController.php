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


        return Inertia::render('admin/ResourceCreate', [
            'redirect' => url()->previous(),
            'node' => $node
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
}

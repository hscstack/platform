<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController as BaseResourceController;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResourceController extends BaseResourceController
{
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            if ($request->user()->can('moderate resources') && $resource->file_path) {
                Storage::delete($resource->file_path);
            }
            $validated['file_path'] = $request->file('file')->store("resources/{$validated['resource_type']}s");
        }

        if ($request->user()->can('moderate resources')) {
            $resource->update($validated);

            return back()->with('success', 'Resource updated successfully.');
        }

        $this->ensureUnderPendingLimit(1, false);

        ResourceChangeRequest::recordUpdate(
            Auth::id(),
            $resource,
            $validated
        );

        return back()->with('success', 'Resource update submitted for moderation.');
    }

    public function destroy(Resource $resource)
    {
        if (Auth::user()?->can('moderate resources')) {
            if ($resource->file_path) {
                Storage::delete($resource->file_path);
            }
            $resource->delete();

            return redirect()->back()->with('success', 'Resource deleted successfully.');
        }

        $this->ensureUnderPendingLimit(1, false);

        ResourceChangeRequest::recordDelete(Auth::id(), $resource);

        return redirect()->back()->with('success', 'Resource deletion request submitted for moderation.');
    }

    public function bulkRename(Request $request, Node $node)
    {
        if ($node->isEffectivelyFrozen()) {
            abort(403, 'This folder is frozen and cannot be modified.');
        }

        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:100'],
            'start_number' => ['nullable', 'integer', 'min:0'],
        ]);

        $rawPrefix = trim($validated['prefix']);
        $cleanPrefix = rtrim($rawPrefix, ' -');
        $startNumber = (int) ($validated['start_number'] ?? 1);

        $resources = $node->resources()->orderBy('id')->get();

        DB::transaction(function () use ($resources, $cleanPrefix, $startNumber) {
            foreach ($resources as $index => $resource) {
                $number = str_pad((string) ($startNumber + $index), 2, '0', STR_PAD_LEFT);
                $title = $cleanPrefix !== '' ? "{$cleanPrefix} - {$number}" : $number;
                $resource->update(['title' => $title]);
            }
        });

        return back()->with('success', "Renamed {$resources->count()} resources successfully.");
    }
}

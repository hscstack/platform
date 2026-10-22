<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController as BaseResourceController;
use App\Models\Resource;
use App\Notifications\ResourceModerationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResourceController extends BaseResourceController
{
    public function approve(Resource $resource)
    {
        abort_unless(Auth::user()?->can('moderate resources'), 403, 'Unauthorized.');

        $resource->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        if ($resource->user && $resource->user_id !== Auth::id()) {
            $resource->user->notify(new ResourceModerationNotification($resource, 'approved'));
        }

        return back()->with('success', 'Resource approved and published successfully.');
    }

    public function reject(Request $request, Resource $resource)
    {
        abort_unless(Auth::user()?->can('moderate resources'), 403, 'Unauthorized.');

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        // Clean up uploaded files in S3/R2
        foreach ($resource->items as $item) {
            if (! str_starts_with($item->source, 'http://') && ! str_starts_with($item->source, 'https://')) {
                Storage::disk('s3')->delete($item->source);
            }
        }

        $resource->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        if ($resource->user && $resource->user_id !== Auth::id()) {
            $resource->user->notify(new ResourceModerationNotification($resource, 'rejected', $validated['admin_note'] ?? null));
        }

        return back()->with('success', 'Resource rejected.');
    }

    public function destroy(Resource $resource)
    {
        $user = Auth::user();
        if (! $user?->can('moderate resources') && ! $user?->can('delete resources')) {
            abort(403, 'Unauthorized. Only users with moderation/deletion permissions can delete resources.');
        }

        if ($resource->node?->isEffectivelyFrozen()) {
            abort(403, 'This folder is frozen and cannot be modified.');
        }

        // Clean up stored items in S3/R2
        foreach ($resource->items as $item) {
            if (! str_starts_with($item->source, 'http://') && ! str_starts_with($item->source, 'https://')) {
                Storage::disk('s3')->delete($item->source);
            }
        }

        $resource->delete();

        return redirect()->back()->with('success', 'Resource deleted successfully.');
    }
}

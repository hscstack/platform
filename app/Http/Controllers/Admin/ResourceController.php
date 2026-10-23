<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController as BaseResourceController;
use App\Models\Resource;
use App\Notifications\ResourceModerationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResourceController extends BaseResourceController
{
    public function moderationIndex(Request $request)
    {
        abort_unless(Auth::user()?->can('moderate resources'), 403, 'Unauthorized.');

        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'])) {
            $status = 'pending';
        }

        $counts = Resource::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $query = Resource::query()
            ->with([
                'user:id,name,username,image_path',
                'node.subject',
            ])
            ->latest('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $resources = $query->paginate(20)->withQueryString();

        $resources->getCollection()->each(function ($res) {
            $res->node?->append('breadcrumb');
        });

        return Inertia::render('admin/moderation/Resources', [
            'resources' => $resources,
            'currentStatus' => $status,
            'counts' => [
                'all' => (int) $counts->sum(),
                'pending' => (int) $counts->get('pending', 0),
                'approved' => (int) $counts->get('approved', 0),
                'rejected' => (int) $counts->get('rejected', 0),
            ],
        ]);
    }

    public function approve(Request $request)
    {
        abort_unless(Auth::user()?->can('moderate resources'), 403, 'Unauthorized.');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:resources,id'],
        ]);

        $resources = Resource::whereIn('id', $validated['ids'])
            ->where('status', '!=', 'approved')
            ->with('user')
            ->get();

        DB::transaction(function () use ($resources) {
            foreach ($resources as $res) {
                $res->update([
                    'status' => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

                if ($res->user) {
                    ResourceModerationNotification::sendTo($res->user, $res, 'approved');
                }
            }
        });

        $count = $resources->count();
        $message = $count === 1
            ? 'Resource approved and published successfully.'
            : "{$count} resources approved and published successfully.";

        return back()->with('success', $message);
    }

    public function reject(Request $request)
    {
        abort_unless(Auth::user()?->can('moderate resources'), 403, 'Unauthorized.');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:resources,id'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $resources = Resource::whereIn('id', $validated['ids'])
            ->where('status', '!=', 'rejected')
            ->with('user')
            ->get();

        $adminNote = $validated['admin_note'] ?? null;

        DB::transaction(function () use ($resources, $adminNote) {
            foreach ($resources as $res) {
                $res->update([
                    'status' => 'rejected',
                    'admin_note' => $adminNote,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

                if ($res->user) {
                    ResourceModerationNotification::sendTo($res->user, $res, 'rejected', $adminNote);
                }
            }
        });

        $count = $resources->count();
        $message = $count === 1
            ? 'Resource rejected.'
            : "{$count} resources rejected.";

        return back()->with('success', $message);
    }

    public function destroy(Resource $resource)
    {
        $user = Auth::user();
        if (! $user?->can('delete resources')) {
            abort(403, 'Unauthorized. Only users with deletion permissions can delete resources.');
        }

        if ($resource->node?->isEffectivelyFrozen()) {
            abort(403, 'This folder is frozen and cannot be modified.');
        }

        // Clean up stored items in S3/R2
        foreach ($resource->items as $item) {
            $source = is_array($item) ? ($item['source'] ?? '') : ($item->source ?? '');
            if ($source && ! str_starts_with($source, 'http://') && ! str_starts_with($source, 'https://')) {
                Storage::disk('s3')->delete($source);
            }
        }

        $resource->delete();

        return redirect()->back()->with('success', 'Resource deleted successfully.');
    }
}

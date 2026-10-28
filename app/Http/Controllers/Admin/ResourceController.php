<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController as BaseResourceController;
use App\Models\Resource;
use App\Notifications\ResourceModerationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResourceController extends BaseResourceController
{
    public function moderationIndex(Request $request)
    {
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
                $updateData = [
                    'status' => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ];

                // First-time approval: reset created_at so it is fresh in Trending & Feed
                // Direct assignment used because created_at is not in $fillable
                $isFirstApproval = is_null($res->reviewed_at);

                $res->update($updateData);

                if ($isFirstApproval) {
                    $res->created_at = now();
                    $res->save();
                }

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
        $resource->delete();

        return redirect()->back()->with('success', 'Resource deleted successfully.');
    }
}

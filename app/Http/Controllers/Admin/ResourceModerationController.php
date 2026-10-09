<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResourceModerationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = ResourceChangeRequest::with([
            'user:id,name,username,image_path',
            'resource',
            'node.subject',
            'reviewer:id,name,username',
        ]);

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->latest()
            ->simplePaginate(15)
            ->withQueryString();

        $requests->getCollection()->each(function ($req) {
            $req->node?->append('breadcrumb');
        });

        $counts = [
            'pending' => ResourceChangeRequest::where('status', 'pending')->count(),
            'approved' => ResourceChangeRequest::where('status', 'approved')->count(),
            'rejected' => ResourceChangeRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('admin/moderation/Resources', [
            'requests' => $requests,
            'counts' => $counts,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:resource_change_requests,id'],
        ]);

        $changeRequests = ResourceChangeRequest::whereIn('id', $validated['ids'])
            ->where('status', 'pending')
            ->with('resource')
            ->get();

        if ($changeRequests->isEmpty()) {
            return back()->with('error', 'Selected requests have already been reviewed.');
        }

        DB::transaction(function () use ($changeRequests) {
            $reviewerId = Auth::id();
            $now = now();

            foreach ($changeRequests as $changeRequest) {
                if ($changeRequest->action_type === 'create') {
                    $payload = $changeRequest->payload ?? [];
                    $payload['node_id'] = $changeRequest->node_id;
                    $payload['user_id'] = $changeRequest->user_id;

                    $resource = Resource::create($payload);
                    $changeRequest->resource_id = $resource->id;
                } elseif ($changeRequest->action_type === 'update') {
                    $resource = $changeRequest->resource;

                    if ($resource) {
                        $newFilePath = $changeRequest->payload['file_path'] ?? null;
                        if ($newFilePath && $resource->file_path && $newFilePath !== $resource->file_path) {
                            Storage::delete($resource->file_path);
                        }

                        $resource->update($changeRequest->payload);
                    }
                } elseif ($changeRequest->action_type === 'delete') {
                    $resource = $changeRequest->resource;

                    if ($resource) {
                        if ($resource->file_path) {
                            Storage::delete($resource->file_path);
                        }
                        $resource->delete();
                    }
                }

                $changeRequest->update([
                    'status' => 'approved',
                    'reviewed_by' => $reviewerId,
                    'reviewed_at' => $now,
                ]);
            }
        });

        $count = $changeRequests->count();
        $message = $count === 1
            ? 'Resource request approved successfully.'
            : "{$count} resource requests approved successfully.";

        return back()->with('success', $message);
    }

    public function reject(Request $request, ?ResourceChangeRequest $changeRequest = null)
    {
        $validated = $request->validate([
            'ids' => ['nullable', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:resource_change_requests,id'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $ids = $validated['ids'] ?? ($changeRequest ? [$changeRequest->id] : []);

        if (empty($ids)) {
            return back()->with('error', 'No change requests selected.');
        }

        $changeRequests = ResourceChangeRequest::whereIn('id', $ids)
            ->where('status', 'pending')
            ->with('resource')
            ->get();

        if ($changeRequests->isEmpty()) {
            return back()->with('error', 'Selected requests have already been reviewed.');
        }

        DB::transaction(function () use ($changeRequests, $validated) {
            $reviewerId = Auth::id();
            $now = now();
            $reason = $validated['rejection_reason'] ?? null;

            foreach ($changeRequests as $item) {
                $stagedFile = $item->payload['file_path'] ?? null;

                if ($stagedFile) {
                    $isNewFile = $item->action_type === 'create'
                        || ($item->action_type === 'update' && $stagedFile !== $item->resource?->file_path);

                    if ($isNewFile) {
                        Storage::delete($stagedFile);
                    }
                }

                $item->update([
                    'status' => 'rejected',
                    'rejection_reason' => $reason,
                    'reviewed_by' => $reviewerId,
                    'reviewed_at' => $now,
                ]);
            }
        });

        $count = $changeRequests->count();
        $message = $count === 1
            ? 'Resource change request rejected.'
            : "{$count} resource requests rejected.";

        return back()->with('success', $message);
    }
}

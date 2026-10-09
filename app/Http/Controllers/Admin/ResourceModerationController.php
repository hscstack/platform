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
        $actionType = $request->query('action_type');

        $query = ResourceChangeRequest::with([
            'user:id,name,username,image_path',
            'resource',
            'node.subject',
            'reviewer:id,name,username',
        ]);

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        if (in_array($actionType, ['create', 'update', 'delete'])) {
            $query->where('action_type', $actionType);
        }

        $requests = $query->latest()
            ->paginate(15)
            ->withQueryString();

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
                'action_type' => $actionType,
            ],
        ]);
    }

    public function approve(ResourceChangeRequest $changeRequest)
    {
        if ($changeRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        DB::transaction(function () use ($changeRequest) {
            if ($changeRequest->action_type === 'create') {
                $resource = Resource::create($changeRequest->payload);
                $changeRequest->resource_id = $resource->id;
            } elseif ($changeRequest->action_type === 'update') {
                $resource = $changeRequest->resource;

                if (! $resource) {
                    abort(404, 'Target resource not found.');
                }

                $newFilePath = $changeRequest->payload['file_path'] ?? null;
                if ($newFilePath && $resource->file_path && $newFilePath !== $resource->file_path) {
                    Storage::delete($resource->file_path);
                }

                $resource->update($changeRequest->payload);
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
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'Resource change request approved successfully.');
    }

    public function reject(Request $request, ResourceChangeRequest $changeRequest)
    {
        if ($changeRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($changeRequest, $validated) {
            $stagedFile = $changeRequest->payload['file_path'] ?? null;

            if ($stagedFile) {
                // If create action, or update action where new file is different from live resource's file
                $isNewFile = $changeRequest->action_type === 'create'
                    || ($changeRequest->action_type === 'update' && $stagedFile !== $changeRequest->resource?->file_path);

                if ($isNewFile) {
                    Storage::delete($stagedFile);
                }
            }

            $changeRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'] ?? null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'Resource change request rejected.');
    }
}

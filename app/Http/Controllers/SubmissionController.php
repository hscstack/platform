<?php

namespace App\Http\Controllers;

use App\Models\ResourceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $userId = Auth::id();

        $query = ResourceChangeRequest::where('user_id', $userId)
            ->with([
                'resource',
                'node.subject',
                'reviewer:id,name,username',
            ]);

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $submissions = $query->latest('id')
            ->simplePaginate(15)
            ->withQueryString();

        $submissions->getCollection()->each(function ($req) {
            $req->node?->append('breadcrumb');
        });

        $pendingCount = ResourceChangeRequest::where('user_id', $userId)->where('status', 'pending')->count();
        $approvedCount = ResourceChangeRequest::where('user_id', $userId)->where('status', 'approved')->count();
        $rejectedCount = ResourceChangeRequest::where('user_id', $userId)->where('status', 'rejected')->count();
        $maxPending = Auth::user()?->is_verified ? 100 : 30;

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'counts' => [
                'all' => $pendingCount + $approvedCount + $rejectedCount,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'max_pending' => $maxPending,
            ],
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function destroy(ResourceChangeRequest $changeRequest)
    {
        abort_unless($changeRequest->user_id === Auth::id(), 403);

        if ($changeRequest->status !== 'pending') {
            return back()->with('error', 'Only pending submissions can be cancelled.');
        }

        if (! empty($changeRequest->payload['file_path'])) {
            Storage::delete($changeRequest->payload['file_path']);
        }

        $changeRequest->delete();

        return back()->with('success', 'আপনার সাবমিশন বাতিল করা হয়েছে।');
    }
}

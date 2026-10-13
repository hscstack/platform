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
        $user = Auth::user();

        $maxLimit = $user->is_verified ? 100 : 30;
        $pendingCount = ResourceChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $totalCount = ResourceChangeRequest::where('user_id', $user->id)->count();
        $approvedCount = ResourceChangeRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();
        $rejectedCount = ResourceChangeRequest::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        $status = $request->query('status');
        $query = ResourceChangeRequest::where('user_id', $user->id)
            ->with(['node.subject', 'reviewer:id,name,username'])
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(15)->withQueryString();

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'quota' => [
                'used' => $pendingCount,
                'max' => $maxLimit,
                'is_verified' => (bool) $user->is_verified,
            ],
            'counts' => [
                'all' => $totalCount,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
            ],
            'currentStatus' => $status ?? 'all',
        ]);
    }

    public function destroy(ResourceChangeRequest $changeRequest)
    {
        if ($changeRequest->user_id !== Auth::id()) {
            abort(403);
        }

        if ($changeRequest->status !== 'pending') {
            return back()->with('error', 'Only pending submissions can be cancelled.');
        }

        $filePath = $changeRequest->payload['file_path'] ?? null;
        if ($filePath) {
            Storage::delete($filePath);
        }

        $changeRequest->delete();

        return back()->with('success', 'Submission cancelled successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ResourceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->with(['resource', 'node.subject'])
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(15)->withQueryString();

        $submissions->getCollection()->each(function ($submission) {
            $submission->node?->append('breadcrumb');
        });

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
}

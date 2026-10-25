<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $maxLimit = $user->is_verified ? 50 : 20;
        $counts = Resource::where('user_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingCount = (int) $counts->get('pending', 0);
        $approvedCount = (int) $counts->get('approved', 0);
        $rejectedCount = (int) $counts->get('rejected', 0);
        $totalCount = (int) $counts->sum();

        $status = $request->query('status');
        $query = Resource::where('user_id', $user->id)
            ->with(['node.subject'])
            // Eager count items for display in submission row previews
            ->withCount('items')
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

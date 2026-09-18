<?php

namespace App\Http\Controllers;

use App\Models\DailyStudyLog;
use App\Models\Node;
use App\Models\NodeCompletion;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudyTrackerController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $course = $user?->curriculum ?: 'hsc';

        $subjects = Subject::where('course', $course)
            ->where('is_trackable', true)
            ->orderBy('sort_order', 'asc')
            ->with(['nodes' => function ($query) {
                $query->where('is_trackable', true)
                    ->orderBy('sort_order', 'asc')
                    ->select('id', 'subject_id', 'name', 'slug', 'sort_order');
            }])
            ->get(['id', 'name', 'english_name', 'slug', 'course', 'sort_order'])
            ->toArray();

        if (! $user) {
            return Inertia::render('Tracker/Index', [
                'course' => $course,
                'subjects' => $subjects,
                'completedNodeIds' => [],
                'todaySeconds' => 0,
                'dailyTargetMinutes' => 120,
                'stats' => [
                    'currentStreak' => 0,
                    'longestStreak' => 0,
                    'totalSeconds' => 0,
                    'totalDays' => 0,
                ],
                'heatmapData' => [],
            ]);
        }

        $completedNodeIds = NodeCompletion::where('user_id', $user->id)
            ->pluck('node_id')
            ->toArray();

        $todayStr = Carbon::today()->format('Y-m-d');
        $todayLog = DailyStudyLog::where('user_id', $user->id)
            ->where('study_date', $todayStr)
            ->first();
        $todaySeconds = (int) ($todayLog?->total_seconds ?? 0);

        $studyData = DailyStudyLog::getHeatmapAndStatsForUser($user);

        return Inertia::render('Tracker/Index', [
            'course' => $course,
            'subjects' => $subjects,
            'completedNodeIds' => $completedNodeIds,
            'todaySeconds' => $todaySeconds,
            'dailyTargetMinutes' => (int) ($user->daily_target_minutes ?: 120),
            'stats' => $studyData['stats'],
            'heatmapData' => $studyData['heatmapData'],
        ]);
    }

    public function toggleNode(Request $request, Node $node)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $existing = NodeCompletion::where('user_id', $user->id)
            ->where('node_id', $node->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            NodeCompletion::create([
                'user_id' => $user->id,
                'node_id' => $node->id,
            ]);
        }

        return back();
    }

    public function logTime(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $validated = $request->validate([
            'seconds' => 'required|integer|min:1|max:86400',
            'date' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $studyDate = $validated['date'] ?? Carbon::today()->format('Y-m-d');

        $log = DailyStudyLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'study_date' => $studyDate,
            ],
            [
                'total_seconds' => 0,
            ]
        );

        $maxAllowed = max(0, 86400 - (int) $log->total_seconds);
        if ($maxAllowed > 0) {
            $secondsToAdd = min((int) $validated['seconds'], $maxAllowed);
            $log->increment('total_seconds', $secondsToAdd);
        }

        return back();
    }

    public function resetToday(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $todayStr = Carbon::today()->format('Y-m-d');
        DailyStudyLog::where('user_id', $user->id)
            ->where('study_date', $todayStr)
            ->update(['total_seconds' => 0]);

        return back();
    }

    public function updateTarget(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $validated = $request->validate([
            'daily_target_minutes' => 'required|integer|min:10|max:1440',
        ]);

        $user->update([
            'daily_target_minutes' => $validated['daily_target_minutes'],
        ]);

        return back();
    }

    public function updateCurriculum(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $validated = $request->validate([
            'curriculum' => 'required|string|in:hsc,ssc',
        ]);

        $newCurriculum = $validated['curriculum'];

        if ($user->curriculum !== $newCurriculum) {
            $user->update([
                'curriculum' => $newCurriculum,
            ]);

            // Clear the entire chapter completion track for the user
            NodeCompletion::where('user_id', $user->id)->delete();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'curriculum' => $newCurriculum,
            ]);
        }

        return redirect()->route('tracker.index')
            ->with('success', 'Curriculum updated to '.strtoupper($newCurriculum).' and chapter track reset.');
    }
}

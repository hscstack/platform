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
        $group = $user?->group ?: 'science';
        $optionalSubjectIds = (array) ($user?->optional_subject_ids ?? []);

        $subjectsQuery = Subject::where('course', $course)
            ->where('is_trackable', true)
            ->where(function ($query) use ($group, $optionalSubjectIds) {
                $query->where(function ($q) use ($group) {
                    $q->where('is_optional', false)
                        ->whereIn('group', [$group, 'common']);
                });

                if (! empty($optionalSubjectIds)) {
                    $query->orWhere(function ($q) use ($group, $optionalSubjectIds) {
                        $q->where('is_optional', true)
                            ->whereIn('group', [$group, 'common'])
                            ->whereIn('id', $optionalSubjectIds);
                    });
                }
            })
            ->orderBy('sort_order', 'asc');

        $subjects = $subjectsQuery
            ->with(['nodes' => function ($query) {
                $query->where('is_trackable', true)
                    ->with('parent:id,name,sort_order')
                    ->orderBy('sort_order', 'asc')
                    ->select('id', 'subject_id', 'parent_id', 'name', 'slug', 'sort_order', 'weight');
            }])
            ->get(['id', 'name', 'slug', 'course', 'group', 'sort_order', 'is_optional'])
            ->toArray();

        // All trackable optional subjects for curriculum modal (so user can toggle optional subjects on/off)
        $groupSubjects = Subject::where('is_trackable', true)
            ->where('is_optional', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name', 'course', 'group', 'is_optional'])
            ->toArray();

        if (! $user) {
            return Inertia::render('Tracker/Index', [
                'course' => $course,
                'group' => $group,
                'subjects' => $subjects,
                'optionalSubjectIds' => [],
                'optionalSubjects' => $groupSubjects,
                'completedNodeIds' => [],
                'todaySeconds' => 0,
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
            'group' => $group,
            'subjects' => $subjects,
            'optionalSubjectIds' => $optionalSubjectIds,
            'optionalSubjects' => $groupSubjects,
            'completedNodeIds' => $completedNodeIds,
            'todaySeconds' => $todaySeconds,
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
            'seconds' => 'required|integer|min:1|max:'.DailyStudyLog::MAX_DAILY_SECONDS,
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

        $maxAllowed = max(0, DailyStudyLog::MAX_DAILY_SECONDS - (int) $log->total_seconds);
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

    public function updateCurriculum(Request $request)
    {
        $validated = $request->validate([
            'curriculum' => 'required|string|in:hsc,ssc',
            'group' => 'required|string|in:science,humanities,commerce',
            // Allow user to persist academic batch (e.g., HSC 26) alongside tracker curriculum
            'batch' => 'nullable|string|max:32',
            'optional_subject_ids' => 'nullable|array',
            'optional_subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $request->user()->update([
            'curriculum' => $validated['curriculum'],
            'group' => $validated['group'],
            'batch' => $validated['batch'] ?? $request->user()->batch,
            'optional_subject_ids' => $validated['optional_subject_ids'] ?? [],
        ]);

        // Return back so user stays on the referring page instead of being redirected
        return back()
            ->with('success', 'Curriculum updated to '.strtoupper($validated['curriculum']).' ('.ucfirst($validated['group']).').');
    }
}

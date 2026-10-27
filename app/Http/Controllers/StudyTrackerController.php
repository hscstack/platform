<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\ChapterCompletion;
use App\Models\DailyStudyLog;
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
            ->with(['chapters' => function ($query) {
                $query->orderBy('sort_order', 'asc')
                    ->select('id', 'subject_id', 'name', 'sort_order', 'weight');
            }])
            ->get(['id', 'name', 'course', 'group', 'sort_order', 'is_optional'])
            ->toArray();

        // Support frontend property alias 'nodes' for smooth transition
        foreach ($subjects as &$subj) {
            $subj['nodes'] = $subj['chapters'] ?? [];
        }
        unset($subj);

        // All optional subjects for curriculum modal (so user can toggle optional subjects on/off)
        $groupSubjects = Subject::where('is_optional', true)
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
                'completedChapterIds' => [],
                'completedNodeIds' => [],
                'todaySeconds' => 0,
            ]);
        }

        $completedChapterIds = ChapterCompletion::where('user_id', $user->id)
            ->pluck('chapter_id')
            ->toArray();

        $todayStr = Carbon::today()->format('Y-m-d');
        $todayLog = DailyStudyLog::where('user_id', $user->id)
            ->where('study_date', $todayStr)
            ->first();
        $todaySeconds = (int) ($todayLog?->total_seconds ?? 0);

        return Inertia::render('Tracker/Index', [
            'course' => $course,
            'group' => $group,
            'subjects' => $subjects,
            'optionalSubjectIds' => $optionalSubjectIds,
            'optionalSubjects' => $groupSubjects,
            'completedChapterIds' => $completedChapterIds,
            'completedNodeIds' => $completedChapterIds,
            'todaySeconds' => $todaySeconds,
        ]);
    }

    public function toggleChapter(Request $request, Chapter $chapter)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $existing = ChapterCompletion::where('user_id', $user->id)
            ->where('chapter_id', $chapter->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ChapterCompletion::create([
                'user_id' => $user->id,
                'chapter_id' => $chapter->id,
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

        DailyStudyLog::where('user_id', $user->id)
            ->where('study_date', Carbon::today()->format('Y-m-d'))
            ->delete();

        return back()->with('success', 'Reset today');
    }

    public function updateCurriculum(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Authentication required');
        }

        $validated = $request->validate([
            'curriculum' => 'required|in:hsc,ssc',
            'group' => 'required|in:science,humanities,commerce',
            'batch' => 'nullable|string|max:10',
            'optional_subject_ids' => 'nullable|array',
            'optional_subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $user->update([
            'curriculum' => $validated['curriculum'],
            'group' => $validated['group'],
            'batch' => $validated['batch'] ?? null,
            'optional_subject_ids' => $validated['optional_subject_ids'] ?? [],
        ]);

        return back()->with('success', 'Curriculum preferences updated');
    }
}

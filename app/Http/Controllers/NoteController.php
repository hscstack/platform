<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Resource;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $course = $request->query('curriculum') ?? $request->query('course');
        if (! in_array($course, ['hsc', 'ssc'], true)) {
            $course = $request->cookie('preferred_course') === 'ssc' ? 'ssc' : 'hsc';
        }

        $group = $request->query('group', 'science');
        if (! in_array($group, ['science', 'commerce', 'humanities'], true)) {
            $group = 'science';
        }

        $sort = $request->query('sort', 'trending');
        if (! in_array($sort, ['trending', 'top', 'latest'], true)) {
            $sort = 'trending';
        }

        $type = $request->query('type', 'all');
        $subjectId = $request->query('subject_id');
        $nodeId = $request->query('node_id');
        $search = trim($request->query('search', ''));

        $query = Resource::query()
            ->approved()
            ->with([
                'user:id,name,username,image_path,institution,is_verified',
                'subject:id,name,course,slug,group',
                'node:id,name,slug,subject_id',
            ]);

        // Filter by course and group (unless searching specific @username without explicit track)
        $isAuthorSearch = str_starts_with($search, '@');
        $hasExplicitTrack = $request->filled('curriculum') || $request->filled('course') || $request->filled('group');

        if (! $isAuthorSearch || $hasExplicitTrack) {
            $query->where(function ($q) use ($course, $group) {
                $q->whereHas('subject', function ($s) use ($course, $group) {
                    $s->where('course', $course)
                        ->whereIn('group', [$group, 'common']);
                })->orWhereHas('node.subject', function ($s) use ($course, $group) {
                    $s->where('course', $course)
                        ->whereIn('group', [$group, 'common']);
                });
            });
        }

        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId)
                    ->orWhereHas('node', fn ($n) => $n->where('subject_id', $subjectId));
            });
        }

        if ($nodeId === 'whole' || $nodeId === 'general') {
            $query->whereNull('node_id');
        } elseif (! empty($nodeId) && is_numeric($nodeId)) {
            $query->where('node_id', (int) $nodeId);
        }

        if (in_array($type, ['note', 'video', 'practical', 'other'], true)) {
            $query->where('resource_type', $type);
        }

        if (! empty($search)) {
            if ($isAuthorSearch) {
                $username = ltrim($search, '@');
                $query->whereHas('user', fn ($u) => $u->where('username', $username));
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }

        if ($sort === 'latest') {
            $query->latest('id');
        } elseif ($sort === 'top') {
            $query->orderByDesc('vote_score')->latest('id');
        } else {
            // Trending with time decay formula: (vote_score / (hours_old + 2))
            // Surfaces high-engagement recent notes ahead of older static posts.
            $isSqlite = DB::getDriverName() === 'sqlite';
            if ($isSqlite) {
                $query->orderByRaw('(vote_score / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
            } else {
                $query->orderByRaw('(vote_score / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
            }
        }

        $resources = $query->simplePaginate(16)->withQueryString();

        // Query available subjects for filter dropdown (cached)
        $subjects = Cache::rememberForever("curriculum_subjects_{$course}_{$group}", function () use ($course, $group) {
            return Subject::where('is_disabled', false)
                ->where('course', $course)
                ->whereIn('group', [$group, 'common'])
                ->orderBy('sort_order')
                ->with(['nodes' => function ($q) {
                    $q->whereNull('parent_id')
                        ->select('id', 'subject_id', 'parent_id', 'name', 'slug')
                        ->orderBy('sort_order');
                }])
                ->get(['id', 'name', 'slug', 'course', 'group'])
                ->toArray();
        });

        $allowUploads = (bool) AppSetting::get('allow_resource_uploads', true);

        return Inertia::render('Notes/Index', [
            'resources' => $resources,
            'subjects' => $subjects,
            'allow_resource_uploads' => $allowUploads,
            'filters' => [
                'curriculum' => $course,
                'course' => $course,
                'group' => $group,
                'subject_id' => $subjectId ? (int) $subjectId : null,
                'node_id' => $nodeId,
                'type' => $type,
                'sort' => $sort,
                'search' => $search,
            ],
        ]);
    }
}

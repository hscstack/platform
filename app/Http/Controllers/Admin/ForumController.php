<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Report;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'curriculum' => $request->input('curriculum'),
            'subject_id' => $request->input('subject_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'recent'),
        ];

        $postsQuery = ForumPost::query()
            ->with([
                'user:id,name,username,image_path,institution',
                'subject:id,name,course,slug',
                'node:id,name,slug',
            ])
            ->when($filters['curriculum'] ?? null, fn ($q, $c) => $q->where('curriculum', $c))
            ->when($filters['subject_id'] ?? null, function ($q, $s) {
                if ($s === 'other') {
                    $q->whereNull('subject_id');
                } else {
                    $q->where('subject_id', $s);
                }
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                match ($status) {
                    'published' => $q->where('is_published', true),
                    'unpublished' => $q->where('is_published', false),
                    'locked' => $q->where('is_locked', true),
                    'unlocked' => $q->where('is_locked', false),
                    'answered' => $q->where('is_answered', true),
                    'unanswered' => $q->where('is_answered', false),
                    default => null,
                };
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            });

        match ($filters['sort'] ?? 'recent') {
            'answers' => $postsQuery->orderByDesc('answers_count')->latest(),
            'votes' => $postsQuery->orderByDesc('vote_score')->latest(),
            default => $postsQuery->latest(),
        };

        $posts = $postsQuery->paginate(20)->withQueryString();

        $subjects = Subject::select('id', 'name', 'course', 'slug')
            ->orderBy('sort_order')
            ->get();

        $pendingReportsCount = Report::where('status', 'pending')
            ->whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])
            ->count();

        return Inertia::render('admin/forums/Index', [
            'posts' => $posts,
            'subjects' => $subjects,
            'filters' => $filters,
            'stats' => [
                'totalPosts' => ForumPost::count(),
                'unpublishedCount' => ForumPost::where('is_published', false)->count(),
                'lockedCount' => ForumPost::where('is_locked', true)->count(),
                'pendingReportsCount' => $pendingReportsCount,
            ],
        ]);
    }

    public function toggleLock(ForumPost $post)
    {
        $post->update([
            'is_locked' => ! $post->is_locked,
        ]);

        $status = $post->is_locked ? 'locked' : 'unlocked';

        return back()->with('success', "Discussion has been {$status}.");
    }

    public function togglePublish(ForumPost $post)
    {
        $post->update([
            'is_published' => ! $post->is_published,
        ]);

        $status = $post->is_published ? 'published' : 'unpublished';

        return back()->with('success', "Discussion has been {$status}.");
    }

    public function destroy(ForumPost $post)
    {
        if ($post->image_path) {
            Storage::delete($post->image_path);
        }

        $answerImages = $post->answers()->whereNotNull('image_path')->pluck('image_path')->all();
        if (! empty($answerImages)) {
            Storage::delete($answerImages);
        }

        // Clean up reports associated with post and its answers
        Report::where('reportable_type', ForumPost::class)->where('reportable_id', $post->id)->delete();
        $answerIds = $post->answers()->pluck('id');
        if ($answerIds->isNotEmpty()) {
            Report::where('reportable_type', ForumAnswer::class)->whereIn('reportable_id', $answerIds)->delete();
        }

        $post->delete();

        return back()->with('success', 'Discussion post deleted successfully.');
    }

    public function reports()
    {
        $reports = Report::with([
            'reporter:id,name,username',
            'reportedUser:id,name,username,chat_banned_until',
            'reportable',
        ])
            ->whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])
            ->latest('id')
            ->take(100)
            ->get()
            ->map(function ($report) {
                $postSlug = null;
                $postTitle = null;

                if ($report->reportable instanceof ForumPost) {
                    $postSlug = $report->reportable->slug;
                    $postTitle = $report->reportable->title;
                } elseif ($report->reportable instanceof ForumAnswer) {
                    $report->reportable->loadMissing('post:id,title,slug');
                    $postSlug = $report->reportable->post?->slug;
                    $postTitle = $report->reportable->post?->title;
                }

                return [
                    'id' => $report->id,
                    'reporter_id' => $report->reporter_id,
                    'reporter' => $report->reporter ? [
                        'id' => $report->reporter->id,
                        'name' => $report->reporter->name,
                        'username' => $report->reporter->username,
                    ] : null,
                    'reported_user_id' => $report->reported_user_id,
                    'reported_user_name' => $report->reported_user_name,
                    'reported_user_username' => $report->reported_user_username,
                    'reported_user' => $report->reportedUser ? [
                        'id' => $report->reportedUser->id,
                        'name' => $report->reportedUser->name,
                        'username' => $report->reportedUser->username,
                        'chat_banned_until' => $report->reportedUser->chat_banned_until?->toIso8601String(),
                        'is_chat_banned' => $report->reportedUser->isChatBanned(),
                    ] : null,
                    'reportable_type' => class_basename($report->reportable_type),
                    'reportable_id' => $report->reportable_id,
                    'post_slug' => $postSlug,
                    'post_title' => $postTitle,
                    'content_snapshot' => $report->content_snapshot,
                    'message_sent_at' => $report->message_sent_at?->toIso8601String(),
                    'reason' => $report->reason,
                    'status' => $report->status,
                    'created_at' => $report->created_at->toIso8601String(),
                ];
            });

        return Inertia::render('admin/forums/Reports', [
            'reports' => $reports,
            'pendingCount' => Report::where('status', 'pending')->whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])->count(),
            'reviewedCount' => Report::where('status', 'reviewed')->whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])->count(),
            'dismissedCount' => Report::where('status', 'dismissed')->whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])->count(),
        ]);
    }

    public function updateReportStatus(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,dismissed'],
        ]);

        $report->update(['status' => $validated['status']]);

        return back()->with('success', 'Report status updated.');
    }

    public function deleteReport(Report $report)
    {
        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }

    public function clearReports(Request $request)
    {
        $status = $request->input('status');

        if ($status && in_array($status, ['pending', 'reviewed', 'dismissed'], true)) {
            Report::whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])->where('status', $status)->delete();
            $message = "All {$status} forum reports have been deleted.";
        } else {
            Report::whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])->delete();
            $message = 'All forum report records have been deleted.';
        }

        return back()->with('success', $message);
    }

    public function settings()
    {
        $bannedWords = AppSetting::get('global_chat_banned_words', '');
        $bannedWordsText = is_array($bannedWords) ? implode(', ', $bannedWords) : (string) $bannedWords;

        return Inertia::render('admin/forums/Settings', [
            'settings' => [
                'posting_enabled' => (bool) AppSetting::get('forum_posting_enabled', true),
                'comments_enabled' => (bool) AppSetting::get('forum_comments_enabled', true),
                'disabled_reason' => (string) AppSetting::get('forum_disabled_reason', ''),
                'auto_unpublish_threshold' => (int) AppSetting::get('forum_auto_unpublish_threshold', 3),
                'profanity_filter_enabled' => (bool) AppSetting::get('global_chat_profanity_filter_enabled', true),
                'banned_words' => $bannedWordsText,
            ],
            'pendingReportsCount' => Report::where('status', 'pending')->whereIn('reportable_type', [ForumPost::class, ForumAnswer::class])->count(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'posting_enabled' => ['required', 'boolean'],
            'comments_enabled' => ['required', 'boolean'],
            'disabled_reason' => ['nullable', 'string', 'max:255'],
            'auto_unpublish_threshold' => ['required', 'integer', 'min:1', 'max:50'],
            'profanity_filter_enabled' => ['required', 'boolean'],
            'banned_words' => ['nullable', 'string'],
        ]);

        AppSetting::set('forum_posting_enabled', $validated['posting_enabled'], 'boolean');
        AppSetting::set('forum_comments_enabled', $validated['comments_enabled'], 'boolean');
        AppSetting::set('forum_disabled_reason', $validated['disabled_reason'] ?? '', 'string');
        AppSetting::set('forum_auto_unpublish_threshold', $validated['auto_unpublish_threshold'], 'integer');
        AppSetting::set('global_chat_profanity_filter_enabled', $validated['profanity_filter_enabled'], 'boolean');
        AppSetting::set('global_chat_banned_words', $validated['banned_words'] ?? '', 'string');

        return back()->with('success', 'Forum settings updated successfully.');
    }
}

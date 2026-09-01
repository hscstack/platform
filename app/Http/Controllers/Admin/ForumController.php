<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Report;
use App\Notifications\ForumStatusNotification;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $postsQuery = ForumPost::query()
            ->with([
                'user:id,name,username,banned_until',
            ])
            ->when(in_array($filters['status'] ?? null, ['approved', 'pending', 'flagged', 'rejected'], true), function ($q) use ($filters) {
                $q->where('moderation_status', $filters['status']);
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
            })
            ->latest();

        $posts = $postsQuery->paginate(20)->withQueryString();

        return Inertia::render('admin/forums/Index', [
            'posts' => $posts,
            'filters' => $filters,
            'stats' => [
                'totalPosts' => ForumPost::count(),
                'pendingCount' => ForumPost::where('moderation_status', 'pending')->count(),
                'flaggedCount' => ForumPost::where('moderation_status', 'flagged')->count(),
                'rejectedCount' => ForumPost::where('moderation_status', 'rejected')->count(),
                'approvedCount' => ForumPost::where('moderation_status', 'approved')->count(),
            ],
        ]);
    }

    public function toggleLock(ForumPost $post)
    {
        $post->update([
            'is_locked' => ! $post->is_locked,
        ]);

        $status = $post->is_locked ? 'locked' : 'unlocked';

        if ($post->user_id && $post->user_id !== auth()->id()) {
            $post->user->notify(new ForumStatusNotification($post, $status));
        }

        return back()->with('success', "Discussion has been {$status}.");
    }

    public function updateModerationStatus(Request $request, ForumPost $post)
    {
        $validated = $request->validate([
            'moderation_status' => ['required', 'string', 'in:approved,pending,flagged,rejected'],
        ]);

        $newStatus = $validated['moderation_status'];

        $post->update(['moderation_status' => $newStatus]);

        if ($post->user_id && $post->user_id !== auth()->id()) {
            $post->user->notify(new ForumStatusNotification($post, $newStatus));
        }

        return back()->with('success', "Discussion status updated to {$newStatus}.");
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

        $post->delete();

        return back()->with('success', 'Discussion post deleted successfully.');
    }

    public function reports()
    {
        $reports = Report::with([
            'reporter:id,name,username',
            'reportedUser:id,name,username,banned_until',
            'reportable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    ForumAnswer::class => ['post:id,title,slug'],
                ]);
            },
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
                        'banned_until' => $report->reportedUser->banned_until?->toIso8601String(),
                        'is_banned' => $report->reportedUser->isBanned(),
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
        abort_unless(in_array($report->reportable_type, [ForumPost::class, ForumAnswer::class], true), 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,dismissed'],
        ]);

        $report->update(['status' => $validated['status']]);

        return back()->with('success', 'Report status updated.');
    }

    public function deleteReport(Report $report)
    {
        abort_unless(in_array($report->reportable_type, [ForumPost::class, ForumAnswer::class], true), 404);

        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }

    public function clearReports(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,reviewed,dismissed'],
        ]);

        $status = $validated['status'] ?? null;

        if ($status) {
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
                'approval_mode' => (string) AppSetting::get('forum_approval_mode', 'auto'),
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
            'approval_mode' => ['required', 'string', 'in:auto,manual'],
            'posting_enabled' => ['required', 'boolean'],
            'comments_enabled' => ['required', 'boolean'],
            'disabled_reason' => ['nullable', 'string', 'max:255'],
            'auto_unpublish_threshold' => ['required', 'integer', 'min:0', 'max:50'],
            'profanity_filter_enabled' => ['required', 'boolean'],
            'banned_words' => ['nullable', 'string'],
        ]);

        AppSetting::set('forum_approval_mode', $validated['approval_mode'], 'string');
        AppSetting::set('forum_posting_enabled', $validated['posting_enabled'], 'boolean');
        AppSetting::set('forum_comments_enabled', $validated['comments_enabled'], 'boolean');
        AppSetting::set('forum_disabled_reason', $validated['disabled_reason'] ?? '', 'string');
        AppSetting::set('forum_auto_unpublish_threshold', $validated['auto_unpublish_threshold'], 'integer');
        AppSetting::set('global_chat_profanity_filter_enabled', $validated['profanity_filter_enabled'], 'boolean');
        AppSetting::set('global_chat_banned_words', $validated['banned_words'] ?? '', 'string');

        return back()->with('success', 'Forum settings updated successfully.');
    }
}

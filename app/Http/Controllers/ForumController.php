<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\Node;
use App\Models\Subject;
use App\Rules\CleanText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'curriculum' => $request->input('curriculum'),
            'subject_id' => $request->input('subject_id'),
            'node_id' => $request->input('node_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'recent'),
            'my_posts' => $request->input('my_posts'),
        ];

        $postsQuery = ForumPost::query()
            ->when(! ($request->filled('my_posts') && auth()->check()), fn ($q) => $q->approved())
            ->with([
                'user:id,name,username,image_path,institution',
                'subject:id,name,course,slug',
                'node:id,name,slug',
            ])
            ->filter($filters);

        $posts = $postsQuery->paginate(15)->withQueryString();

        if ($userId = auth()->id()) {
            $postIds = $posts->getCollection()->pluck('id');
            $userVotes = ForumVote::where('user_id', $userId)
                ->where('voteable_type', ForumPost::class)
                ->whereIn('voteable_id', $postIds)
                ->pluck('value', 'voteable_id');

            $posts->getCollection()->transform(function ($post) use ($userVotes) {
                $post->user_vote = $userVotes[$post->id] ?? null;

                return $post;
            });
        }

        $subjects = Cache::remember('forum_filter_subjects', now()->addDay(), function () {
            return Subject::select('id', 'name', 'course', 'slug')
                ->with(['nodes' => fn ($q) => $q->whereNull('parent_id')->select('id', 'subject_id', 'name', 'slug')->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        });

        return Inertia::render('Forum/Index', [
            'posts' => $posts,
            'subjects' => $subjects,
            'filters' => $filters,
            'postingEnabled' => (bool) AppSetting::get('forum_posting_enabled', true),
            'commentsEnabled' => (bool) AppSetting::get('forum_comments_enabled', true),
            'disabledReason' => (string) AppSetting::get('forum_disabled_reason', ''),
        ]);
    }

    public function show(Request $request, ForumPost $post): Response
    {
        $user = auth()->user();
        $isAuthor = $user && $user->id === $post->user_id;
        $isAdmin = $user && $user->can('view admin');

        if (! $post->isApproved() && ! $isAuthor && ! $isAdmin) {
            abort(404);
        }

        $post->load([
            'user:id,name,username,image_path,institution',
            'subject:id,name,course,slug',
            'node:id,name,slug',
        ]);

        $answers = $post->directAnswers()
            ->with([
                'user:id,name,username,image_path,institution',
                'replies' => fn ($q) => $q->with([
                    'user:id,name,username,image_path,institution',
                    'parent:id,user_id',
                    'parent.user:id,name,username',
                ])->orderBy('created_at', 'asc'),
            ])
            ->orderByDesc('vote_score')
            ->orderBy('created_at', 'asc')
            ->paginate(20)
            ->withQueryString();

        if ($userId = auth()->id()) {
            $postVote = ForumVote::where('user_id', $userId)
                ->where('voteable_type', ForumPost::class)
                ->where('voteable_id', $post->id)
                ->value('value');
            $post->user_vote = $postVote;

            $allAnswerIds = collect();
            foreach ($answers->items() as $directAnswer) {
                $allAnswerIds->push($directAnswer->id);
                foreach ($directAnswer->replies as $reply) {
                    $allAnswerIds->push($reply->id);
                }
            }

            $answerVotes = ForumVote::where('user_id', $userId)
                ->where('voteable_type', ForumAnswer::class)
                ->whereIn('voteable_id', $allAnswerIds)
                ->pluck('value', 'voteable_id');

            $answers->getCollection()->transform(function ($answer) use ($answerVotes) {
                $answer->user_vote = $answerVotes[$answer->id] ?? null;
                if ($answer->relationLoaded('replies')) {
                    $answer->replies->transform(function ($reply) use ($answerVotes) {
                        $reply->user_vote = $answerVotes[$reply->id] ?? null;

                        return $reply;
                    });
                }

                return $answer;
            });
        }

        $upvoters = ForumVote::where('voteable_type', ForumPost::class)
            ->where('voteable_id', $post->id)
            ->where('value', 1)
            ->with('user:id,name,username,image_path,institution')
            ->latest()
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        return Inertia::render('Forum/Show', [
            'post' => $post,
            'answers' => $answers,
            'upvoters' => $upvoters,
            'commentsEnabled' => (bool) AppSetting::get('forum_comments_enabled', true),
            'disabledReason' => (string) AppSetting::get('forum_disabled_reason', ''),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return redirect()->route('forum.index')->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        $isPostingEnabled = (bool) AppSetting::get('forum_posting_enabled', true);
        if (! $isPostingEnabled) {
            $reason = AppSetting::get('forum_disabled_reason', 'Creating new questions is temporarily paused.');

            return redirect()->route('forum.index')->with('error', $reason ?: 'Creating new questions is temporarily paused.');
        }

        $subjects = Cache::remember('forum_filter_subjects', now()->addDay(), function () {
            return Subject::select('id', 'name', 'course', 'slug')
                ->with(['nodes' => fn ($q) => $q->whereNull('parent_id')->select('id', 'subject_id', 'name', 'slug')->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        });

        return Inertia::render('Forum/Create', [
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        $isPostingEnabled = (bool) AppSetting::get('forum_posting_enabled', true);
        if (! $isPostingEnabled) {
            $reason = AppSetting::get('forum_disabled_reason', 'Creating new questions is temporarily paused.');

            return back()->with('error', $reason ?: 'Creating new questions is temporarily paused.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:255', new CleanText],
            'body' => ['required', 'string', 'min:10', 'max:30000', new CleanText],
            'curriculum' => ['required', 'in:hsc,ssc'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'node_id' => ['nullable', 'exists:nodes,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('forum/posts');
        }

        $subjectId = $validated['subject_id'] ?? null;
        $nodeId = $validated['node_id'] ?? null;

        if ($subjectId) {
            $subject = Subject::find($subjectId);
            if (! $subject || $subject->course !== $validated['curriculum']) {
                $subjectId = null;
                $nodeId = null;
            } elseif ($nodeId) {
                $nodeExists = Node::where('id', $nodeId)->where('subject_id', $subjectId)->exists();
                if (! $nodeExists) {
                    $nodeId = null;
                }
            }
        } else {
            $nodeId = null;
        }

        $approvalMode = AppSetting::get('forum_approval_mode', 'auto');
        $moderationStatus = $approvalMode === 'auto' ? 'approved' : 'pending';

        $post = ForumPost::create([
            'user_id' => auth()->id(),
            'subject_id' => $subjectId,
            'node_id' => $nodeId,
            'curriculum' => $validated['curriculum'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image_path' => $imagePath,
            'is_locked' => false,
            'moderation_status' => $moderationStatus,
        ]);

        $message = $moderationStatus === 'pending'
            ? 'Question submitted successfully and is pending moderator review.'
            : 'Question posted successfully!';

        return redirect()->route('forum.show', $post->slug)->with('success', $message);
    }

    public function destroy(ForumPost $post): RedirectResponse
    {
        abort_unless(auth()->id() === $post->user_id || auth()->user()?->can('manage forums'), 403);

        if ($post->image_path) {
            Storage::delete($post->image_path);
        }

        $answerImages = $post->answers()->whereNotNull('image_path')->pluck('image_path')->all();
        if (! empty($answerImages)) {
            Storage::delete($answerImages);
        }

        $post->delete();

        return redirect()->route('forum.index')->with('success', 'Question deleted successfully.');
    }

    public function toggleAnswered(Request $request, ForumPost $post): RedirectResponse
    {
        abort_unless(auth()->id() === $post->user_id || auth()->user()?->can('manage forums'), 403);

        $post->update([
            'is_answered' => ! $post->is_answered,
        ]);

        return back();
    }

    public function report(Request $request, ForumPost $post): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($post->reports()->where('reporter_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You have already reported this question.',
            ], 422);
        }

        $author = $post->user;

        $report = $post->reports()->create([
            'reporter_id' => $user->id,
            'reported_user_id' => $author?->id,
            'reported_user_name' => $author?->name,
            'reported_user_username' => $author?->username,
            'content_snapshot' => $post->title."\n\n".$post->body,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Auto-unpublish threshold check
        $threshold = (int) AppSetting::get('forum_auto_unpublish_threshold', 3);
        if ($threshold > 0) {
            $pendingReportsCount = $post->reports()->where('status', 'pending')->count();

            if ($pendingReportsCount >= $threshold) {
                $post->update(['moderation_status' => 'flagged']);
            }
        }

        return response()->json([
            'message' => 'Question reported successfully. Our moderation team will review it.',
            'report_id' => $report->id,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Chapter;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ForumAnswerNotification;
use App\Notifications\ForumQuestionPendingNotification;
use App\Notifications\ForumReportNotification;
use App\Notifications\ForumStatusNotification;
use App\Notifications\ForumVoteNotification;
use App\Notifications\UserMentionNotification;
use App\Rules\CleanText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    public function index(Request $request): Response
    {
        $myPosts = $request->boolean('my_posts');

        $filters = [
            'curriculum' => $request->input('curriculum'),
            'subject_id' => $request->input('subject_id'),
            'node_id' => $request->input('node_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'recent'),
            'my_posts' => $myPosts,
        ];

        $postsQuery = ForumPost::query()
            ->when(! ($myPosts && auth()->check()), fn ($q) => $q->approved())
            ->with([
                'user:id,name,username,image_path,institution,is_verified',
                'subject:id,name,course',
                'chapter:id,name',
            ])
            ->filter($filters);

        $posts = $postsQuery->simplePaginate(15)->withQueryString();

        $userId = auth()->id();
        $userVotes = collect();

        if ($userId) {
            $postIds = $posts->getCollection()->pluck('id');
            $userVotes = ForumVote::where('user_id', $userId)
                ->where('voteable_type', ForumPost::class)
                ->whereIn('voteable_id', $postIds)
                ->pluck('value', 'voteable_id');
        }

        $posts->getCollection()->transform(function ($post) use ($userVotes) {
            $post->user_vote = $userVotes[$post->id] ?? null;

            if ($post->is_anonymous) {
                $post->user_id = null;
                $post->setRelation('user', (object) [
                    'id' => null,
                    'name' => 'Anonymous Student',
                    'username' => null,
                    'image_path' => null,
                    'image_url' => null,
                    'institution' => null,
                    'is_verified' => false,
                ]);
            }

            return $post;
        });

        $subjects = Cache::remember('forum_filter_subjects', now()->addDay(), function () {
            return Subject::select('id', 'name', 'course')
                ->with(['chapters' => fn ($q) => $q->select('id', 'subject_id', 'name')->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
                ->map(function ($s) {
                    $sArray = $s->toArray();
                    $sArray['nodes'] = $sArray['chapters'] ?? [];

                    return $sArray;
                })
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
        $isModerator = $user && $user->can('manage forums');

        if (! $post->isApproved() && ! $isAuthor && ! $isModerator) {
            abort(404);
        }

        $post->load([
            'user:id,name,username,image_path,institution,is_verified',
            'subject:id,name,course',
            'chapter:id,name',
        ]);

        $actualPostAuthorId = $post->user_id;

        if ($post->is_anonymous) {
            $post->user_id = null;
            $post->setRelation('user', (object) [
                'id' => null,
                'name' => 'Anonymous Student',
                'username' => null,
                'image_path' => null,
                'image_url' => null,
                'institution' => null,
                'is_verified' => false,
            ]);
        }

        $answers = $post->directAnswers()
            ->with([
                'user:id,name,username,image_path,institution,is_verified',
                'replies' => fn ($q) => $q->with([
                    'user:id,name,username,image_path,institution,is_verified',
                    'parent:id,user_id',
                    'parent.user:id,name,username',
                ])->orderBy('created_at', 'asc'),
            ])
            ->orderByDesc('vote_score')
            ->orderBy('created_at', 'asc')
            ->paginate(20)
            ->withQueryString();

        $maskAuthor = function ($item) use ($post, $actualPostAuthorId) {
            $isAuthorOfPost = $item->user_id === $actualPostAuthorId;
            $item->is_author = $isAuthorOfPost;

            if ($post->is_anonymous && $isAuthorOfPost) {
                $item->user_id = null;
                $item->setRelation('user', (object) [
                    'id' => null,
                    'name' => 'Anonymous Student',
                    'username' => null,
                    'image_path' => null,
                    'image_url' => null,
                    'institution' => null,
                    'is_verified' => false,
                ]);
            }

            return $item;
        };

        $answers->getCollection()->transform(function ($answer) use ($maskAuthor) {
            $maskAuthor($answer);
            if ($answer->relationLoaded('replies')) {
                $answer->replies->transform($maskAuthor);
            }

            return $answer;
        });

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
            ->with('user:id,name,username,image_path,institution,is_verified')
            ->latest('id')
            ->take(20)
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

    public function generateUploadUrl(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            return response()->json([
                'message' => 'You are suspended from uploading.',
            ], 403);
        }

        $isPostingEnabled = (bool) AppSetting::get('forum_posting_enabled', true);
        if (! $isPostingEnabled) {
            return response()->json([
                'message' => AppSetting::get('forum_disabled_reason', 'Creating new questions is temporarily paused.'),
            ], 403);
        }

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:4'],
            'files.*.content_type' => ['required', 'string', 'in:image/jpeg,image/png,image/webp'],
            'files.*.size' => ['required', 'integer', 'min:1', 'max:5242880'],
        ]);

        $results = [];

        foreach ($validated['files'] as $fileData) {
            $extension = match ($fileData['content_type']) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };

            $key = 'forum/posts/'.(string) Str::uuid().'.'.$extension;
            $signed = Storage::disk('s3')->temporaryUploadUrl(
                $key,
                now()->addMinutes(15),
                [
                    'ContentType' => $fileData['content_type'],
                    'ContentLengthRange' => [1, $fileData['size']],
                ]
            );

            $results[] = [
                'key' => $key,
                'upload_url' => is_array($signed) ? $signed['url'] : $signed,
            ];
        }

        return response()->json(['urls' => $results]);
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
            'body' => ['required', 'string', 'min:5', 'max:30000', new CleanText],
            'curriculum' => ['required', 'in:hsc,ssc'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'chapter_id' => ['nullable', 'exists:chapters,id'],
            'node_id' => ['nullable', 'exists:chapters,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['string', 'max:255', 'regex:#^forum/posts/[A-Za-z0-9._/-]+$#', 'not_regex:#\\.\\.#'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $images = [];
        if (! empty($validated['images'])) {
            $images = array_slice($validated['images'], 0, 4);
        } elseif ($request->hasFile('image')) {
            $images = [$request->file('image')->store('forum/posts')];
        }

        $subjectId = $validated['subject_id'] ?? null;
        $chapterId = $validated['chapter_id'] ?? $validated['node_id'] ?? null;

        if ($subjectId) {
            $subject = Subject::find($subjectId);
            if (! $subject || $subject->course !== $validated['curriculum']) {
                $subjectId = null;
                $chapterId = null;
            } elseif ($chapterId) {
                $chapterExists = Chapter::where('id', $chapterId)->where('subject_id', $subjectId)->exists();
                if (! $chapterExists) {
                    $chapterId = null;
                }
            }
        } else {
            $chapterId = null;
        }

        $approvalMode = AppSetting::get('forum_approval_mode', 'auto');
        $isAnonymous = $request->boolean('is_anonymous');
        $moderationStatus = ($isAnonymous || $approvalMode !== 'auto') ? 'pending' : 'approved';

        $baseSlug = Str::limit(Str::slug(Str::words(strip_tags($validated['body']), 8, '')) ?: 'post', 200, '');
        $slug = $baseSlug.'-'.Str::lower(Str::random(6));

        $post = ForumPost::create([
            'user_id' => auth()->id(),
            'subject_id' => $subjectId,
            'chapter_id' => $chapterId,
            'curriculum' => $validated['curriculum'],
            'slug' => $slug,
            'body' => $validated['body'],
            'images' => ! empty($images) ? $images : null,
            'is_locked' => false,
            'is_anonymous' => $isAnonymous,
            'moderation_status' => $moderationStatus,
        ]);

        if ($moderationStatus === 'pending') {
            $moderators = User::permission('manage forums')
                ->select(['id', 'name', 'email'])
                ->get();

            if ($moderators->isNotEmpty()) {
                Notification::send($moderators, new ForumQuestionPendingNotification($post, $user));
            }
        }

        $message = $moderationStatus === 'pending'
            ? 'Question submitted successfully and is pending moderator review.'
            : 'Question posted successfully!';

        return redirect()->route('forum.show', $post->slug)->with('success', $message);
    }

    public function destroy(ForumPost $post): RedirectResponse
    {
        abort_unless(auth()->id() === $post->user_id || auth()->user()?->can('manage forums'), 403);

        $postImages = $post->images ?? [];
        if (is_array($postImages)) {
            foreach ($postImages as $img) {
                if ($img && ! str($img)->startsWith(['http://', 'https://'])) {
                    Storage::delete($img);
                }
            }
        } elseif (! empty($post->image_path)) {
            Storage::delete($post->image_path);
        }

        $answerImages = $post->answers()->whereNotNull('image_path')->pluck('image_path')->all();
        if (! empty($answerImages)) {
            Storage::delete($answerImages);
        }

        $post->delete();

        return redirect()->route('forum.index')->with('success', 'Question deleted successfully.');
    }

    public function report(Request $request, ForumPost $post): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $author = $post->user;

        $report = DB::transaction(function () use ($post, $user, $validated, $author) {
            // Lock the post row so concurrent threshold checks are serialized
            ForumPost::where('id', $post->id)->lockForUpdate()->first();

            if ($post->reports()->where('reporter_id', $user->id)->exists()) {
                return null; // Signal duplicate
            }

            $report = $post->reports()->create([
                'reporter_id' => $user->id,
                'reported_user_id' => $author?->id,
                'reported_user_name' => $author?->name,
                'reported_user_username' => $author?->username,
                'content_snapshot' => $post->body,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            // Auto-unpublish threshold check (inside transaction to prevent double-trigger)
            $threshold = (int) AppSetting::get('forum_auto_unpublish_threshold', 3);
            if ($threshold > 0) {
                $pendingReportsCount = $post->reports()->where('status', 'pending')->count();

                if ($pendingReportsCount >= $threshold) {
                    $wasNotFlagged = $post->moderation_status !== 'flagged';
                    $post->update(['moderation_status' => 'flagged']);

                    if ($wasNotFlagged && $author) {
                        $author->notify(new ForumStatusNotification($post, 'flagged', 'Your question was flagged by community reports', 'Your question has been temporarily hidden pending review due to community reports.'));
                    }
                }
            }

            return $report;
        });

        if ($report === null) {
            return response()->json([
                'message' => 'You have already reported this question.',
            ], 422);
        }

        $moderators = User::permission('manage forums')
            ->select(['id', 'name', 'email'])
            ->get();

        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new ForumReportNotification($report, $user));
        }

        return response()->json([
            'message' => 'Question reported successfully. Our moderation team will review it.',
            'report_id' => $report->id,
        ], 201);
    }

    public function storeAnswer(Request $request, ForumPost $post): RedirectResponse
    {
        $user = auth()->user();

        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        if (! $post->isApproved()) {
            return back()->with('error', 'This discussion is currently not open for new replies.');
        }

        if ($post->is_locked) {
            return back()->with('error', 'This discussion has been locked. New replies are disabled.');
        }

        $isCommentsEnabled = (bool) AppSetting::get('forum_comments_enabled', true);
        if (! $isCommentsEnabled && (! $user || ! $user->can('view admin'))) {
            $reason = AppSetting::get('forum_disabled_reason', 'Submitting comments is temporarily paused.');

            return back()->with('error', $reason ?: 'Submitting comments is temporarily paused.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000', new CleanText],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'parent_id' => ['nullable', 'exists:forum_answers,id'],
            'reply_to_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parent = ForumAnswer::where('forum_post_id', $post->id)->findOrFail($parentId);
            $parentId = $parent->parent_id ?: $parent->id;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('forum/answers');
        }

        $answer = $post->answers()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'body' => $validated['body'],
            'image_path' => $imagePath,
        ]);

        $post->increment('answers_count');

        $mentionedUserIds = [];

        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $validated['body'], $matches)) {
            $mentionedUsernames = array_unique(array_filter($matches[1], fn ($u) => strcasecmp($u, $user->username) !== 0));
            if (! empty($mentionedUsernames)) {
                $mentionedUsers = User::whereIn('username', array_slice($mentionedUsernames, 0, 5))
                    ->select(['id', 'name', 'username', 'email'])
                    ->get();

                if ($mentionedUsers->isNotEmpty()) {
                    Notification::send(
                        $mentionedUsers,
                        new UserMentionNotification(
                            sender: $user,
                            contextTitle: "discussion \"{$post->snippet(40)}\"",
                            content: $answer->body,
                            url: route('forum.show', $post->slug),
                            itemType: 'forum_post',
                            itemId: $post->id,
                        )
                    );
                    $mentionedUserIds = $mentionedUsers->pluck('id')->all();
                }
            }
        }

        if (isset($parent)) {
            $parentAuthor = $parent->user;
            if ($parentAuthor && $parentAuthor->id !== $user->id && empty($mentionedUserIds)) {
                ForumAnswerNotification::sendTo($parentAuthor, $post, $answer, true);
                $mentionedUserIds[] = $parentAuthor->id;
            }
        } elseif ($post->user_id && $post->user_id !== $user->id && ! in_array($post->user_id, $mentionedUserIds, true)) {
            if ($post->user) {
                ForumAnswerNotification::sendTo($post->user, $post, $answer, false);
            }
        }

        return back()->with('success', 'Answer posted!');
    }

    public function destroyAnswer(ForumAnswer $answer): RedirectResponse
    {
        abort_unless(auth()->id() === $answer->user_id || auth()->user()?->can('manage forums'), 403);

        $post = $answer->post;
        $replies = $answer->replies;
        $repliesCount = $replies->count();

        DB::transaction(function () use ($answer, $replies, $post, $repliesCount) {
            if ($answer->image_path) {
                Storage::delete($answer->image_path);
            }

            foreach ($replies as $reply) {
                if ($reply->image_path) {
                    Storage::delete($reply->image_path);
                }
            }

            $answer->delete();

            if ($post) {
                $post->decrement('answers_count', 1 + $repliesCount);
            }
        });

        return back()->with('success', 'Deleted.');
    }

    public function reportAnswer(Request $request, ForumAnswer $answer): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($answer->reports()->where('reporter_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You have already reported this answer.',
            ], 422);
        }

        $author = $answer->user;

        $report = $answer->reports()->create([
            'reporter_id' => $user->id,
            'reported_user_id' => $author?->id,
            'reported_user_name' => $author?->name,
            'reported_user_username' => $author?->username,
            'content_snapshot' => $answer->body,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        $moderators = User::permission('manage forums')
            ->select(['id', 'name', 'email'])
            ->get();

        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new ForumReportNotification($report, $user));
        }

        return response()->json([
            'message' => 'Answer reported successfully. Our moderation team will review it.',
            'report_id' => $report->id,
        ], 201);
    }

    public function votePost(Request $request, ForumPost $post): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        $validated = $request->validate([
            'value' => ['required', 'in:1,-1'],
        ]);

        $this->performVote($post, (int) $validated['value'], $user);

        return back();
    }

    public function voteAnswer(Request $request, ForumAnswer $answer): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        $validated = $request->validate([
            'value' => ['required', 'in:1,-1'],
        ]);

        $this->performVote($answer, (int) $validated['value'], $user);

        return back();
    }

    private function performVote($model, int $value, $user): void
    {
        $userId = $user->id;
        $isFirstTimeUpvote = false;

        DB::transaction(function () use ($model, $userId, $value, &$isFirstTimeUpvote) {
            $locked = $model::where('id', $model->id)->lockForUpdate()->first();
            if (! $locked) {
                return;
            }

            $existing = $locked->votes()->where('user_id', $userId)->lockForUpdate()->first();

            if ($existing) {
                if ($existing->value === $value) {
                    $existing->delete();
                } else {
                    $existing->update(['value' => $value]);
                }
            } else {
                $locked->votes()->create([
                    'user_id' => $userId,
                    'value' => $value,
                ]);

                if ($value === 1) {
                    $isFirstTimeUpvote = true;
                }
            }

            $upvotes = (int) $locked->votes()->where('value', 1)->count();
            $downvotes = (int) $locked->votes()->where('value', -1)->count();

            $locked->updateQuietly([
                'upvotes_count' => $upvotes,
                'downvotes_count' => $downvotes,
                'vote_score' => $upvotes - $downvotes,
            ]);
        });

        if ($isFirstTimeUpvote && $model->user_id !== $userId) {
            ForumVoteNotification::sendTo($model->user, $model, $user);
        }
    }
}

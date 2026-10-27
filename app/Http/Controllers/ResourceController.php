<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resource\StoreResourceRequest;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Models\AppSetting;
use App\Models\Chapter;
use App\Models\Resource;
use App\Models\ResourceComment;
use App\Models\ResourceVote;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ResourceCommentNotification;
use App\Notifications\ResourceVoteNotification;
use App\Notifications\UserMentionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function show($id)
    {
        $cachedData = Cache::remember("resource_public_{$id}", now()->addHours(6), function () use ($id) {
            $resource = Resource::with([
                'user:id,name,username,image_path',
                'chapter:id,name,subject_id',
                'chapter.subject:id,name,course',
                'subject:id,name,course',
            ])->find($id);

            if (! $resource || $resource->status !== 'approved') {
                return null;
            }

            if (! $resource->relationLoaded('subject') || ! $resource->subject) {
                $resource->setRelation('subject', $resource->chapter?->subject);
            }

            $upvoters = ResourceVote::where('resource_id', $id)
                ->where('value', 1)
                ->with('user:id,name,username,image_path,institution,is_verified')
                ->latest('id')
                ->take(20)
                ->get()
                ->pluck('user')
                ->filter()
                ->values();

            return [
                'resource' => $resource->toArray(),
                'upvoters' => $upvoters->toArray(),
            ];
        });

        $user = auth()->user();
        $isModerator = $user?->can('moderate resources');

        if ($cachedData) {
            $resourceData = $cachedData['resource'];
            $upvoters = $cachedData['upvoters'];
            $isOwner = $user && $user->id === ($resourceData['user_id'] ?? null);
        } else {
            // Fallback for non-approved (pending / rejected) viewing by owner or moderator
            $resource = Resource::with([
                'user:id,name,username,image_path',
                'chapter:id,name,subject_id',
                'chapter.subject:id,name,course',
                'subject:id,name,course',
            ])->findOrFail($id);

            if (! $resource->relationLoaded('subject') || ! $resource->subject) {
                $resource->setRelation('subject', $resource->chapter?->subject);
            }

            $isOwner = $user && $user->id === $resource->user_id;

            if ($resource->status !== 'approved' && ! $isModerator && ! $isOwner) {
                abort(404);
            }

            $resourceData = $resource->toArray();
            $upvoters = [];
        }

        $userVote = auth()->check()
            ? ResourceVote::where('resource_id', $id)->where('user_id', auth()->id())->value('value')
            : null;

        $subjects = ($isModerator || $isOwner)
            ? Subject::where('is_disabled', false)
                ->orderBy('sort_order')
                ->with(['chapters' => fn ($q) => $q->select('id', 'subject_id', 'name')->orderBy('sort_order')])
                ->get(['id', 'name', 'course', 'group'])
            : [];

        $comments = ResourceComment::where('resource_id', $id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,image_path,institution,is_verified',
                'replies' => fn ($q) => $q->with(['user:id,name,username,image_path,institution,is_verified']),
            ])
            ->latest('id')
            ->get();

        return Inertia::render('Resource', [
            'resource' => $resourceData,
            'subjects' => $subjects,
            'userVote' => $userVote,
            'upvoters' => $upvoters,
            'comments' => $comments,
            'can_moderate' => (bool) $isModerator,
            'is_owner' => (bool) $isOwner,
        ]);
    }

    public function vote(Request $request, Resource $resource)
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            if ($request->wantsJson()) {
                return response()->json([
                    'error' => "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).",
                ], 403);
            }

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        abort_if($resource->status !== 'approved', 404);

        $validated = $request->validate([
            'value' => ['required', 'in:1,-1'],
        ]);

        $value = (int) $validated['value'];
        $userId = auth()->id();
        $isFirstTimeUpvote = false;

        DB::transaction(function () use ($resource, $userId, $value, &$isFirstTimeUpvote) {
            $lockedResource = Resource::where('id', $resource->id)->lockForUpdate()->first();
            if (! $lockedResource) {
                return;
            }

            $existing = ResourceVote::where('resource_id', $lockedResource->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->value === $value) {
                    $existing->delete();
                } else {
                    $existing->update(['value' => $value]);
                }
            } else {
                ResourceVote::create([
                    'resource_id' => $lockedResource->id,
                    'user_id' => $userId,
                    'value' => $value,
                ]);

                if ($value === 1) {
                    $isFirstTimeUpvote = true;
                }
            }

            $upvotes = (int) ResourceVote::where('resource_id', $lockedResource->id)->where('value', 1)->count();
            $downvotes = (int) ResourceVote::where('resource_id', $lockedResource->id)->where('value', -1)->count();

            $lockedResource->update([
                'upvotes_count' => $upvotes,
                'downvotes_count' => $downvotes,
                'vote_score' => $upvotes - $downvotes,
            ]);
        });

        if ($isFirstTimeUpvote && $resource->user_id && $resource->user_id !== $userId) {
            $resource->loadMissing('user');
            if ($resource->user) {
                ResourceVoteNotification::sendTo($resource->user, $resource, $user);
            }
        }

        Cache::forget("resource_public_{$resource->id}");

        if ($request->wantsJson()) {
            $refreshed = $resource->fresh();
            $currentUserVote = ResourceVote::where('resource_id', $resource->id)
                ->where('user_id', $userId)
                ->value('value');

            return response()->json([
                'success' => true,
                'vote_score' => $refreshed->vote_score,
                'upvotes_count' => $refreshed->upvotes_count,
                'downvotes_count' => $refreshed->downvotes_count,
                'user_vote' => $currentUserVote,
            ]);
        }

        return back();
    }

    public function getMaxPendingSubmissions(): int
    {
        return Auth::user()?->is_verified ? 50 : 20;
    }

    public function ensureCanSubmit(int $incomingCount = 1): void
    {
        $allowUploads = (bool) AppSetting::get('allow_resource_uploads', true);
        if (! $allowUploads && ! Auth::user()?->can('moderate resources')) {
            throw ValidationException::withMessages([
                'upload_paused' => 'নতুন রিসোর্স আপলোড সাময়িকভাবে স্থগিত রাখা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
            ]);
        }

        $user = Auth::user();
        if ($user?->can('moderate resources')) {
            return;
        }

        if ($user?->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();
            throw ValidationException::withMessages([
                'banned' => "আপনি সাময়িকভাবে ব্যান রয়েছেন ({$bannedUntilFormatted} পর্যন্ত)। এই সময়ে কোনো রিসোর্স আপলোড বা পরিবর্তন করা যাবে না।",
            ]);
        }

        $userId = Auth::id();
        $maxLimit = $this->getMaxPendingSubmissions();

        $currentPending = Resource::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        if (($currentPending + $incomingCount) > $maxLimit) {
            throw ValidationException::withMessages([
                'pending_limit' => "আপনি সর্বোচ্চ {$maxLimit}টি কন্টেন্ট আপলোড করার অনুরোধ করতে পারেন। আপনার আপলোডকৃত {$currentPending}টি কন্টেন্ট বর্তমানে পর্যালোচনাধীন রয়েছে, তাই অনুগ্রহ করে অপেক্ষা করুন। ",
            ]);
        }
    }

    public function generateUploadUrl(Request $request)
    {
        try {
            $this->ensureCanSubmit(1);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:50'],
            'files.*.content_type' => ['required', 'string', 'in:image/jpeg,image/png,image/webp'],
            'files.*.size' => ['required', 'integer', 'min:1', 'max:5242880'],
        ]);

        $userId = auth()->id();
        $results = [];

        foreach ($validated['files'] as $fileData) {
            $extension = match ($fileData['content_type']) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };

            $key = 'resources/notes/'.(string) Str::uuid().'.'.$extension;
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

    public function fetchPlaylist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'playlist_url' => ['required', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'সঠিক ইউটিউব প্লেলিস্টের লিংক দিন।',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = $this->resolveYoutubePlaylist($validator->validated()['playlist_url']);

        if (empty($items)) {
            return response()->json([
                'error' => 'Unable to fetch videos. Please provide a valid, public YouTube playlist URL.',
            ], 422);
        }

        return response()->json(['items' => $items]);
    }

    public function store(StoreResourceRequest $request)
    {
        $this->ensureCanSubmit(1);

        $validated = $request->validated();
        $isModerator = $request->user()->can('moderate resources');
        $status = $isModerator ? 'approved' : 'pending';

        $chapterId = ! empty($validated['chapter_id']) ? (int) $validated['chapter_id'] : null;
        $subjectId = $chapterId
            ? (int) (Chapter::where('id', $chapterId)->value('subject_id') ?? $validated['subject_id'])
            : (int) $validated['subject_id'];

        Resource::create([
            'subject_id' => $subjectId,
            'chapter_id' => $chapterId,
            'user_id' => Auth::id(),
            'resource_type' => $validated['resource_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'thumbnail' => $this->computeThumbnailFromItems($validated['items']),
            'items' => $validated['items'],
            'status' => $status,
            'reviewed_by' => $isModerator ? Auth::id() : null,
            'reviewed_at' => $isModerator ? now() : null,
        ]);

        $typeName = ucfirst($validated['resource_type']);
        $message = $isModerator
            ? "{$typeName} created and published successfully."
            : "{$typeName} submitted successfully and is awaiting review.";

        return back()->with('success', $message);
    }

    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $user = Auth::user();
        if ($user?->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();
            throw ValidationException::withMessages([
                'banned' => "আপনি সাময়িকভাবে ব্যান রয়েছেন ({$bannedUntilFormatted} পর্যন্ত)। এই সময়ে কোনো রিসোর্স পরিবর্তন করা যাবে না।",
            ]);
        }

        $validated = $request->validated();

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'resource_type' => $validated['resource_type'],
            'thumbnail' => $this->computeThumbnailFromItems($validated['items']),
            'items' => $validated['items'],
        ];

        if (array_key_exists('chapter_id', $validated)) {
            $chapterId = ! empty($validated['chapter_id']) ? (int) $validated['chapter_id'] : null;
            $updateData['chapter_id'] = $chapterId;

            if ($chapterId) {
                $chapterSubjectId = Chapter::where('id', $chapterId)->value('subject_id');
                if ($chapterSubjectId) {
                    $updateData['subject_id'] = (int) $chapterSubjectId;
                }
            }
        }

        if (array_key_exists('subject_id', $validated) && empty($updateData['subject_id'])) {
            if (! empty($validated['subject_id'])) {
                $updateData['subject_id'] = (int) $validated['subject_id'];
            }
        }

        // If an approved resource is edited by someone without manage/moderate permissions, return it to pending for re-review
        $canEditDirectly = $user && ($user->can('manage resources') || $user->can('moderate resources'));
        if (! $canEditDirectly && $resource->status === 'approved') {
            $updateData['status'] = 'pending';
            $updateData['reviewed_by'] = null;
        }

        $resource->update($updateData);

        Cache::forget("resource_public_{$resource->id}");

        return back()->with('success', 'রিসোর্স সফলভাবে আপডেট করা হয়েছে।');
    }

    private function resolveYoutubePlaylist(string $url): array
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        $playlistId = $query['list'] ?? null;

        if (! $playlistId || ! config('services.youtube.key')) {
            return [];
        }

        $videos = [];
        $pageToken = null;

        do {
            $response = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
                'part' => 'snippet',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => config('services.youtube.key'),
            ]);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            foreach ($data['items'] ?? [] as $item) {
                $videoId = $item['snippet']['resourceId']['videoId'] ?? null;
                $title = $item['snippet']['title'] ?? null;

                if ($videoId && $title !== 'Private video' && $title !== 'Deleted video') {
                    $videos[] = [
                        'format' => 'video',
                        'title' => $title,
                        'source' => "https://www.youtube.com/watch?v={$videoId}",
                    ];
                }
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken && count($videos) < 100);

        return $videos;
    }

    private function computeThumbnailFromItems(array $items): ?string
    {
        $first = $items[0] ?? null;
        if (! $first) {
            return null;
        }

        $format = $first['format'] ?? null;
        $source = $first['source'] ?? null;

        if ($format === 'image' && $source) {
            return $source;
        }

        if ($format === 'video' && $source) {
            if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $source, $matches)) {
                return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
            }
        }

        return null;
    }

    public function mySubmissions(Request $request)
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
            ->with(['chapter.subject', 'subject'])
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(15)->withQueryString();

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

    public function storeComment(Request $request, Resource $resource)
    {
        $user = auth()->user();

        if ($user && $user->isBanned()) {
            return back()->with('error', 'You are temporarily suspended from community participation.');
        }

        // Restrict commenting if resource is not approved, unless user is owner or moderator
        $isModerator = $user?->can('moderate resources');
        $isOwner = $user && $user->id === $resource->user_id;
        if ($resource->status !== 'approved' && ! $isModerator && ! $isOwner) {
            abort(404);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:resource_comments,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parent = ResourceComment::where('resource_id', $resource->id)->find($parentId);
            $parentId = $parent?->parent_id ?: $parent?->id;
        }

        $comment = $resource->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => trim($validated['content']),
        ]);

        $mentionedUserIds = [];

        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->content, $matches)) {
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
                            contextTitle: "\"{$resource->title}\"",
                            content: $comment->content,
                            url: route('resources.show', $resource->id).'#comments',
                            itemType: 'resource',
                            itemId: $resource->id,
                        )
                    );
                    $mentionedUserIds = $mentionedUsers->pluck('id')->all();
                }
            }
        }

        if ($resource->user_id !== $user->id && ! in_array($resource->user_id, $mentionedUserIds, true)) {
            if ($resource->user) {
                ResourceCommentNotification::sendTo($resource->user, $resource, $comment);
            }
        }

        return back()->with('success', 'Comment posted successfully.');
    }

    public function destroyComment(ResourceComment $comment)
    {
        abort_unless(auth()->id() === $comment->user_id || auth()->user()?->can('moderate resources'), 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }
}

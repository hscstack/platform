<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resource\StoreResourceRequest;
use App\Models\Resource;
use App\Models\ResourceVote;
use App\Notifications\ResourceVoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function show($id)
    {
        $resource = Resource::with(['user:id,name,username,image_path', 'node.subject'])->findOrFail($id);

        $user = auth()->user();
        $isModerator = $user?->can('moderate resources');
        $isOwner = $user && $user->id === $resource->user_id;

        // If not approved, only the owner and moderators are allowed to view
        if ($resource->status !== 'approved' && ! $isModerator && ! $isOwner) {
            abort(404);
        }

        $node = $resource->node;

        $userVote = auth()->check()
            ? ResourceVote::where('resource_id', $id)->where('user_id', auth()->id())->value('value')
            : null;

        $upvoters = ResourceVote::where('resource_id', $id)
            ->where('value', 1)
            ->with('user:id,name,username,image_path,institution,is_verified')
            ->latest('id')
            ->take(20)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        return Inertia::render('Resource', [
            'resource' => $resource->toArray(),
            'subject' => $resource->node?->subject?->toArray(),
            'breadcrumb' => $node ? $node->breadcrumb() : [],
            'userVote' => $userVote,
            'upvoters' => $upvoters,
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

        Cache::forget("resource_{$resource->id}");
        Cache::forget("node_resources_{$resource->node_id}");

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

    /**
     * Determine maximum allowed pending submissions for the user.
     * Verified users: 50, Unverified users: 20.
     */
    public function getMaxPendingSubmissions(): int
    {
        return Auth::user()?->is_verified ? 50 : 20;
    }

    /**
     * Check if user is eligible to submit (account age) and within pending quota.
     */
    public function ensureCanSubmit(int $incomingCount = 1): void
    {
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
            'content_type' => ['required', 'string', 'in:image/jpeg,image/png,image/webp'],
            'size' => ['required', 'integer', 'min:1', 'max:5242880'], // Max 5MB per image
        ]);

        $extension = match ($validated['content_type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        $key = 'resources/notes/'.(string) Str::uuid().'.'.$extension;

        $signed = Storage::disk('s3')->temporaryUploadUrl(
            $key,
            now()->addMinutes(15),
            [
                'ContentType' => $validated['content_type'],
            ]
        );

        return response()->json([
            'key' => $key,
            'upload_url' => is_array($signed) ? $signed['url'] : $signed,
        ]);
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

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(StoreResourceRequest $request)
    {
        $this->ensureCanSubmit(1);

        $validated = $request->validated();
        $isModerator = $request->user()->can('moderate resources');
        $status = $isModerator ? 'approved' : 'pending';

        DB::transaction(function () use ($validated, $status, $isModerator) {
            return Resource::create([
                'node_id' => $validated['node_id'],
                'user_id' => Auth::id(),
                'resource_type' => $validated['resource_type'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'thumbnail' => Resource::computeThumbnailFromItems($validated['items']),
                'items' => $validated['items'],
                'status' => $status,
                'reviewed_by' => $isModerator ? Auth::id() : null,
                'reviewed_at' => $isModerator ? now() : null,
            ]);
        });

        $typeName = ucfirst($validated['resource_type']);
        $message = $isModerator
            ? "{$typeName} created and published successfully."
            : "{$typeName} submitted successfully and is awaiting review.";

        return back()->with('success', $message);
    }

    public function update(Request $request, Resource $resource)
    {
        $user = Auth::user();

        if ($user?->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();
            throw ValidationException::withMessages([
                'banned' => "আপনি সাময়িকভাবে ব্যান রয়েছেন ({$bannedUntilFormatted} পর্যন্ত)। এই সময়ে কোনো রিসোর্স পরিবর্তন করা যাবে না।",
            ]);
        }

        if ($resource->node?->isEffectivelyFrozen()) {
            abort(403, 'This folder is frozen and cannot be modified.');
        }

        abort_unless(
            $user?->can('edit resources') || $user?->id === $resource->user_id,
            403,
            'Unauthorized to edit this resource.'
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'resource_type' => ['required', 'string', 'in:note,practical,marked_book,other,video'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.format' => ['required', 'in:image,pdf,video'],
            'items.*.source' => ['required', 'string'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
        ]);

        $formats = array_unique(array_column($validated['items'], 'format'));
        if (count($formats) > 1) {
            throw ValidationException::withMessages([
                'items' => 'একটি রিসোর্সে একাধিক ফরম্যাট (ছবি, PDF, ভিডিও) একসাথে রাখা যাবে না।',
            ]);
        }

        $format = $formats[0] ?? null;
        if ($validated['resource_type'] === 'video' && $format !== 'video') {
            throw ValidationException::withMessages([
                'items' => 'ভিডিও রিসোর্সে শুধুমাত্র ভিডিও লিংক থাকতে পারে।',
            ]);
        } elseif ($validated['resource_type'] !== 'video' && $format === 'video') {
            throw ValidationException::withMessages([
                'items' => 'নোট বা ডকুমেন্টে ভিডিও লিংক যুক্ত করা যাবে না।',
            ]);
        }

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'resource_type' => $validated['resource_type'],
            'thumbnail' => Resource::computeThumbnailFromItems($validated['items']),
            'items' => $validated['items'],
        ];

        // If an approved resource is edited by someone without 'edit resources', return it to pending for re-review
        $user = auth()->user();
        $canEditDirectly = $user && $user->can('edit resources');
        if (! $canEditDirectly && $resource->status === 'approved') {
            $updateData['status'] = 'pending';
            $updateData['reviewed_by'] = null;
            $updateData['reviewed_at'] = null;
        }

        $resource->update($updateData);

        Cache::forget("resource_{$resource->id}");
        Cache::forget("node_resources_{$resource->node_id}");

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
}

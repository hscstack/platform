<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resource\StoreDocumentRequest;
use App\Http\Requests\Resource\StoreVideoRequest;
use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceVote;
use App\Notifications\ResourceVoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function show($id)
    {
        $data = Cache::remember("resource_{$id}", 3600, function () use ($id) {
            $resource = Resource::with(['user:id,name,username,image_path', 'node.subject', 'items'])->findOrFail($id);

            return [
                'resource' => $resource->toArray(),
                'subject' => $resource->node?->subject?->toArray(),
            ];
        });

        $node = Node::find($data['resource']['node_id']);

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

        return Inertia::render('Resource', array_merge($data, [
            'breadcrumb' => $node ? $node->breadcrumb() : [],
            'userVote' => $userVote,
            'upvoters' => $upvoters,
        ]));
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
        $this->ensureCanSubmit(1);

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

        $url = Storage::disk('s3')->temporaryUploadUrl(
            $key,
            now()->addMinutes(15),
            [
                'ContentType' => $validated['content_type'],
                'ContentLength' => $validated['size'],
            ]
        );

        return response()->json([
            'key' => $key,
            'upload_url' => $url,
        ]);
    }

    public function storeDocument(StoreDocumentRequest $request)
    {
        $this->ensureCanSubmit(1);

        $validated = $request->validated();
        $isModerator = $request->user()->can('moderate resources');

        $status = $isModerator ? 'approved' : 'pending';

        DB::transaction(function () use ($validated, $status, $isModerator) {
            $resource = Resource::create([
                'node_id' => $validated['node_id'],
                'user_id' => Auth::id(),
                'resource_type' => $validated['resource_type'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $status,
                'reviewed_by' => $isModerator ? Auth::id() : null,
                'reviewed_at' => $isModerator ? now() : null,
            ]);

            $resource->items()->createMany($validated['items']);

            return $resource;
        });

        $typeName = ucfirst($validated['resource_type']);
        $message = $isModerator
            ? "{$typeName} created and published successfully."
            : "{$typeName} submitted successfully and is awaiting review.";

        return back()->with('success', $message);
    }

    public function storeVideo(StoreVideoRequest $request)
    {
        $this->ensureCanSubmit(1);

        $validated = $request->validated();
        $isModerator = $request->user()->can('moderate resources');
        $status = $isModerator ? 'approved' : 'pending';

        $items = $this->resolveYoutubePlaylist($validated['playlist_url']);

        if (empty($items)) {
            return back()->with('error', 'Unable to fetch videos. Please provide a valid, public YouTube playlist URL.');
        }

        DB::transaction(function () use ($validated, $items, $status, $isModerator) {
            $resource = Resource::create([
                'node_id' => $validated['node_id'],
                'user_id' => Auth::id(),
                'resource_type' => 'video',
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $status,
                'reviewed_by' => $isModerator ? Auth::id() : null,
                'reviewed_at' => $isModerator ? now() : null,
            ]);

            $resource->items()->createMany($items);

            return $resource;
        });

        $message = $isModerator
            ? 'Video playlist created and published successfully.'
            : 'Video playlist submitted successfully and is awaiting review.';

        return back()->with('success', $message);
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

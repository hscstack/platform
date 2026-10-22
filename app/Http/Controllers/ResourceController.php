<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resource\BulkImageStoreRequest;
use App\Http\Requests\Resource\BulkVideoStoreRequest;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\ResourceVote;
use App\Notifications\ResourceVoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
     * Verified users: 100, Unverified users: 30.
     */
    public function getMaxPendingSubmissions(): int
    {
        return Auth::user()?->is_verified ? 100 : 30;
    }

    /**
     * Check if user is eligible to submit (account age) and within pending change requests quota.
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

        if ($user?->created_at && $user->created_at->diffInHours(now()) < 24) {
            throw ValidationException::withMessages([
                'account_age' => 'আপনার অ্যাকাউন্টটি নতুন। স্প্যাম রোধে অ্যাকাউন্ট তৈরির ২৪ ঘণ্টা পর রিসোর্স আপলোড করা যাবে।',
            ]);
        }

        $userId = Auth::id();
        $maxLimit = $this->getMaxPendingSubmissions();

        $currentPending = ResourceChangeRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        if (($currentPending + $incomingCount) > $maxLimit) {
            throw ValidationException::withMessages([
                'pending_limit' => "আপনি সর্বোচ্চ {$maxLimit}টি কন্টেন্ট আপলোড করার অনুরোধ করতে পারেন। আপনার আপলোডকৃত {$currentPending}টি কন্টেন্ট বর্তমানে পর্যালোচনাধীন রয়েছে, তাই অনুগ্রহ করে অপেক্ষা করুন। ",
            ]);
        }
    }

    public function store(StoreResourceRequest $request)
    {
        $validated = $request->validated();
        $isModerator = $request->user()->can('moderate resources');

        if (! $isModerator) {
            $this->ensureCanSubmit(1);
        }

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store("resources/{$validated['resource_type']}s");
        }

        if ($isModerator) {
            $validated['user_id'] = Auth::id();
            Resource::create($validated);

            return back()->with('success', 'Resource created successfully.');
        }

        ResourceChangeRequest::recordCreate(
            Auth::id(),
            (int) $validated['node_id'],
            $validated
        );

        return redirect()->route('submissions.index')->with('success', 'Resource submitted for moderation.');
    }

    public function storeBulkImages(BulkImageStoreRequest $request)
    {
        $validated = $request->validated();
        $filesCount = count($request->file('files') ?? []);
        $isModerator = $request->user()->can('moderate resources');

        if (! $isModerator) {
            $this->ensureCanSubmit($filesCount);
        }

        $userId = Auth::id();
        $nodeId = (int) $validated['node_id'];

        DB::transaction(function () use ($request, $validated, $userId, $nodeId, $isModerator) {
            $content = $validated['content'] ?? null;

            foreach ($request->file('files') as $index => $file) {
                $filePath = $file->store('resources/images');
                $title = $validated['custom_titles'][$index];

                if ($isModerator) {
                    Resource::create([
                        'user_id' => $userId,
                        'node_id' => $nodeId,
                        'title' => $title,
                        'content' => $content,
                        'resource_type' => 'image',
                        'file_path' => $filePath,
                    ]);
                } else {
                    ResourceChangeRequest::recordCreate($userId, $nodeId, [
                        'title' => $title,
                        'content' => $content,
                        'resource_type' => 'image',
                        'file_path' => $filePath,
                    ]);
                }
            }
        });

        if ($isModerator) {
            return back()->with('success', 'Images uploaded successfully.');
        }

        return redirect()->route('submissions.index')->with('success', 'Images submitted for moderation.');
    }

    public function storeBulkVideos(BulkVideoStoreRequest $request)
    {
        $validated = $request->validated();
        $isModerator = $request->user()->can('moderate resources');

        if (! $isModerator) {
            $this->ensureCanSubmit(1);
        }

        $playlistUrl = $validated['playlist_url'];

        // Extract playlist ID
        parse_str(parse_url($playlistUrl, PHP_URL_QUERY), $query);

        $playlistId = $query['list'] ?? null;

        if (! $playlistId) {
            return back()->with('error', 'Invalid Youtube URL');
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
                return back()->with('error', 'Unable to fetch youtube url');
            }

            $data = $response->json();

            foreach ($data['items'] as $item) {
                $videos[] = [
                    'title' => $item['snippet']['title'],
                    'video_id' => $item['snippet']['resourceId']['videoId'],
                    'position' => $item['snippet']['position'],
                ];
            }

            if (! $isModerator && count($videos) >= $this->getMaxPendingSubmissions()) {
                break;
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        if (! empty($validated['is_reversed'])) {
            $videos = array_reverse($videos);
        }

        // Apply naming strategy
        foreach ($videos as $index => &$video) {
            if ($validated['naming_strategy'] === 'youtube') {
                $video['title'] = $video['title'];
            } else {
                $number = str_pad(
                    ($validated['start_number'] ?? 1) + $index,
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $prefix = trim($validated['naming_prefix'] ?? '');
                $video['title'] = $prefix !== '' ? "{$prefix} - {$number}" : $number;
            }
        }

        $userId = Auth::id();
        $nodeId = (int) $validated['node_id'];
        $videosCount = count($videos);
        $isModerator = $request->user()->can('moderate resources');

        if (! $isModerator) {
            $this->ensureCanSubmit($videosCount);
        }

        DB::transaction(function () use ($videos, $userId, $nodeId, $isModerator) {
            foreach ($videos as $video) {
                $finalUrl = "https://www.youtube.com/watch?v={$video['video_id']}";

                if ($isModerator) {
                    Resource::create([
                        'user_id' => $userId,
                        'node_id' => $nodeId,
                        'title' => $video['title'],
                        'resource_type' => 'video',
                        'external_url' => $finalUrl,
                    ]);
                } else {
                    ResourceChangeRequest::recordCreate($userId, $nodeId, [
                        'title' => $video['title'],
                        'resource_type' => 'video',
                        'external_url' => $finalUrl,
                    ]);
                }
            }
        });

        if ($isModerator) {
            return back()->with('success', 'YouTube playlist imported successfully.');
        }

        return redirect()->route('submissions.index')->with('success', 'YouTube playlist imported and submitted for moderation.');
    }
}

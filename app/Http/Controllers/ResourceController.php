<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resource\BulkImageStoreRequest;
use App\Http\Requests\Resource\BulkVideoStoreRequest;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\ResourceCompletion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResourceController extends Controller
{
    /**
     * Determine maximum allowed pending submissions for the user.
     * Verified users: 100, Unverified users: 30.
     */
    protected function getMaxPendingSubmissions(): int
    {
        return Auth::user()?->is_verified ? 100 : 30;
    }

    /**
     * Check if user would exceed their pending change requests quota or fails eligibility.
     * Throws standard ValidationException so it returns as a typed error in Inertia errors.
     */
    protected function ensureUnderPendingLimit(int $incomingCount = 1, bool $checkAge = true): void
    {
        if (Auth::user()?->can('moderate resources')) {
            return;
        }

        $user = Auth::user();

        if ($checkAge && $user && $user->created_at && $user->created_at->isAfter(now()->subDay())) {
            throw ValidationException::withMessages([
                'pending_limit' => 'নোট বা রিসোর্স আপলোড করার জন্য আপনার অ্যাকাউন্টের বয়স কমপক্ষে ২৪ ঘণ্টা হতে হবে।',
            ]);
        }

        $userId = Auth::id();
        $maxLimit = $this->getMaxPendingSubmissions();

        $currentPending = ResourceChangeRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        if (($currentPending + $incomingCount) > $maxLimit) {
            throw ValidationException::withMessages([
                'pending_limit' => "আপনি সর্বোচ্চ {$maxLimit}টি কন্টেন্ট আপলোড করার অনুরোধ করতে পারেন। আপনার আপলোডকৃত {$currentPending}টি কন্টেন্ট বর্তমানে পর্যালোচনাধীন রয়েছে, তাই অনুগ্রহ করে অপেক্ষা করুন।",
            ]);
        }
    }

    public function store(StoreResourceRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store("resources/{$validated['resource_type']}s");
        }

        if ($request->user()->can('moderate resources')) {
            $validated['user_id'] = Auth::id();
            Resource::create($validated);

            return back()->with('success', 'Resource created successfully.');
        }

        $this->ensureUnderPendingLimit(1);

        ResourceChangeRequest::recordCreate(
            Auth::id(),
            (int) $validated['node_id'],
            $validated
        );

        return back()->with('success', 'Resource submitted for moderation.');
    }

    public function storeBulkImages(BulkImageStoreRequest $request)
    {
        $validated = $request->validated();
        $filesCount = count($request->file('files') ?? []);
        $isModerator = $request->user()->can('moderate resources');

        if (! $isModerator) {
            $this->ensureUnderPendingLimit($filesCount);
        }

        $userId = Auth::id();
        $nodeId = (int) $validated['node_id'];

        DB::transaction(function () use ($request, $validated, $userId, $nodeId, $isModerator) {
            foreach ($request->file('files') as $index => $file) {
                $filePath = $file->store('resources/images');
                $title = $validated['custom_titles'][$index];

                if ($isModerator) {
                    Resource::create([
                        'user_id' => $userId,
                        'node_id' => $nodeId,
                        'title' => $title,
                        'resource_type' => 'image',
                        'file_path' => $filePath,
                    ]);
                } else {
                    ResourceChangeRequest::recordCreate($userId, $nodeId, [
                        'title' => $title,
                        'resource_type' => 'image',
                        'file_path' => $filePath,
                    ]);
                }
            }
        });

        $message = $isModerator ? 'Images uploaded successfully.' : 'Images submitted for moderation.';

        return back()->with('success', $message);
    }

    public function storeBulkVideos(BulkVideoStoreRequest $request)
    {
        $validated = $request->validated();

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
            $this->ensureUnderPendingLimit($videosCount);
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

        $message = $isModerator
            ? 'YouTube playlist imported successfully.'
            : 'YouTube playlist imported and submitted for moderation.';

        return back()->with('success', $message);
    }

    public function show($id)
    {
        $data = Cache::rememberForever("resource_{$id}", function () use ($id) {
            $resource = Resource::with(['user', 'node.subject'])->findOrFail($id);

            $previousResourceId = Resource::where('node_id', $resource->node_id)
                ->where('id', '<', $resource->id)
                ->orderByDesc('id')
                ->value('id');

            $nextResourceId = Resource::where('node_id', $resource->node_id)
                ->where('id', '>', $resource->id)
                ->orderBy('id')
                ->value('id');

            return [
                'resource' => $resource->toArray(),
                'subject' => $resource->node?->subject?->toArray(),
                'previousResourceId' => $previousResourceId,
                'nextResourceId' => $nextResourceId,
            ];
        });

        $isCompleted = auth()->check()
            ? ResourceCompletion::where('resource_id', $id)->where('user_id', auth()->id())->exists()
            : false;

        $completionsCount = ResourceCompletion::where('resource_id', $id)->count();

        $completers = ResourceCompletion::where('resource_id', $id)
            ->with(['user:id,name,username,image_path,institution,is_verified'])
            ->latest()
            ->take(10)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        $node = Node::find($data['resource']['node_id']);

        return Inertia::render('Resource', array_merge($data, [
            'breadcrumb' => $node ? $node->breadcrumb() : [],
            'isCompleted' => $isCompleted,
            'completionsCount' => $completionsCount,
            'completers' => $completers,
        ]));
    }

    public function toggleComplete(Resource $resource)
    {
        $user = auth()->user();
        $existing = $resource->completions()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $resource->completions()->create(['user_id' => $user->id]);
        }

        return back();
    }
}

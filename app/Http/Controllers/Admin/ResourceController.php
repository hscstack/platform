<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\BulkImageStoreRequest;
use App\Http\Requests\Resource\BulkVideoStoreRequest;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
     * Check if user would exceed their pending change requests quota.
     * Throws standard ValidationException so it returns as a typed error in Inertia errors.
     */
    protected function ensureUnderPendingLimit(int $incomingCount = 1): void
    {
        if (Auth::user()?->can('moderate resources')) {
            return;
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

    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            if ($request->user()->can('moderate resources') && $resource->file_path) {
                Storage::delete($resource->file_path);
            }
            $validated['file_path'] = $request->file('file')->store("resources/{$validated['resource_type']}s");
        }

        if ($request->user()->can('moderate resources')) {
            $resource->update($validated);

            return back()->with('success', 'Resource updated successfully.');
        }

        $this->ensureUnderPendingLimit(1);

        ResourceChangeRequest::recordUpdate(
            Auth::id(),
            $resource,
            $validated
        );

        return back()->with('success', 'Resource update submitted for moderation.');
    }

    public function destroy(Resource $resource)
    {
        if (Auth::user()?->can('moderate resources')) {
            if ($resource->file_path) {
                Storage::delete($resource->file_path);
            }
            $resource->delete();

            return redirect()->back()->with('success', 'Resource deleted successfully.');
        }

        $this->ensureUnderPendingLimit(1);

        ResourceChangeRequest::recordDelete(Auth::id(), $resource);

        return redirect()->back()->with('success', 'Resource deletion request submitted for moderation.');
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

    public function bulkRename(Request $request, Node $node)
    {
        if ($node->isEffectivelyFrozen()) {
            abort(403, 'This folder is frozen and cannot be modified.');
        }

        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:100'],
            'start_number' => ['nullable', 'integer', 'min:0'],
        ]);

        $rawPrefix = trim($validated['prefix']);
        $cleanPrefix = rtrim($rawPrefix, ' -');
        $startNumber = (int) ($validated['start_number'] ?? 1);

        $resources = $node->resources()->orderBy('id')->get();

        DB::transaction(function () use ($resources, $cleanPrefix, $startNumber) {
            foreach ($resources as $index => $resource) {
                $number = str_pad((string) ($startNumber + $index), 2, '0', STR_PAD_LEFT);
                $title = $cleanPrefix !== '' ? "{$cleanPrefix} - {$number}" : $number;
                $resource->update(['title' => $title]);
            }
        });

        return back()->with('success', "Renamed {$resources->count()} resources successfully.");
    }
}

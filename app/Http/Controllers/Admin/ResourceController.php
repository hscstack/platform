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

class ResourceController extends Controller
{
    public function store(StoreResourceRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store("resources/{$validated['resource_type']}s");
        }

        ResourceChangeRequest::recordCreate(
            Auth::id(),
            (int) $validated['node_id'],
            $validated
        );

        return back()->with('success', 'Resource submitted for moderation.');
    }

    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        if ($resource->pendingChangeRequest()->exists()) {
            return back()->with('error', 'This resource already has a pending change request under review.');
        }

        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store("resources/{$validated['resource_type']}s");
        }

        ResourceChangeRequest::recordUpdate(
            Auth::id(),
            $resource,
            $validated
        );

        return back()->with('success', 'Resource update submitted for moderation.');
    }

    public function destroy(Resource $resource)
    {
        if ($resource->pendingChangeRequest()->exists()) {
            return back()->with('error', 'This resource already has a pending change request under review.');
        }

        ResourceChangeRequest::recordDelete(Auth::id(), $resource);

        return redirect()->back()->with('success', 'Resource deletion request submitted for moderation.');
    }

    public function storeBulkImages(BulkImageStoreRequest $request)
    {
        $validated = $request->validated();
        $userId = Auth::id();
        $nodeId = (int) $validated['node_id'];

        DB::transaction(function () use ($request, $validated, $userId, $nodeId) {
            foreach ($request->file('files') as $index => $file) {
                ResourceChangeRequest::recordCreate($userId, $nodeId, [
                    'title' => $validated['custom_titles'][$index],
                    'resource_type' => 'image',
                    'file_path' => $file->store('resources/images'),
                ]);
            }
        });

        return back()->with('success', 'Images submitted for moderation.');
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

        DB::transaction(function () use ($videos, $userId, $nodeId) {
            foreach ($videos as $video) {
                $finalUrl = "https://www.youtube.com/watch?v={$video['video_id']}";

                ResourceChangeRequest::recordCreate($userId, $nodeId, [
                    'title' => $video['title'],
                    'resource_type' => 'video',
                    'external_url' => $finalUrl,
                ]);
            }
        });

        return back()->with('success', 'YouTube playlist imported and submitted for moderation.');
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

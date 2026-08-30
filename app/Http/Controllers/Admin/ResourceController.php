<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\BulkImageStoreRequest;
use App\Http\Requests\Resource\BulkVideoStoreRequest;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Models\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function store(StoreResourceRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store("resources/{$validated['resource_type']}s");
            $validated['file_path'] = $path;
        }

        Resource::create($validated);

        return back()->with('success', 'Resource created successfully.');
    }

    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {

            if ($resource->file_path) {
                Storage::delete($resource->file_path);
            }

            $path = $request->file('file')
                ->store("resources/{$validated['resource_type']}s");

            $validated['file_path'] = $path;
        }

        $resource->update($validated);

        return back()->with('success', 'Resource updated successfully.');
    }

    public function destroy(Resource $resource)
    {
        if ($resource->file_path) {
            Storage::delete($resource->file_path);
        }

        $resource->delete();

        return redirect()->back()->with('success', 'Resource deleted successfully.');
    }

    public function storeBulkImages(BulkImageStoreRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            foreach ($request->file('files') as $index => $file) {

                $validated['title'] = $validated['custom_titles'][$index];
                $validated['file_path'] = $file->store('resources/images');
                $validated['user_id'] = Auth::id();
                $validated['resource_type'] = 'image';

                Resource::create($validated);
            }
        });

        return back()->with('success', 'Images uploaded successfully.');
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

        DB::transaction(function () use ($videos, $validated, $userId) {
            foreach ($videos as $video) {
                $finalUrl = "https://www.youtube.com/watch?v={$video['video_id']}";

                Resource::create([
                    'user_id' => $userId,
                    'node_id' => $validated['node_id'],
                    'title' => $video['title'],
                    'resource_type' => 'video',
                    'external_url' => $finalUrl,
                ]);
            }
        });

        return back()->with('success', 'YouTube playlist imported successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\BulkImageStoreRequest;
use App\Http\Requests\Resource\BulkVideoStoreRequest;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Models\Node;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResourceController extends Controller
{

    public function create(Request $request)
    {
        $node = Node::findOrFail($request->node_id);


        return Inertia::render('admin/ResourceCreateOrEdit', [
            'redirect' => url()->previous(),
            'node' => $node
        ]);
    }
    public function edit(Resource $resource)
    {
        $node = $resource->node;


        return Inertia::render('admin/ResourceCreateOrEdit', [
            'redirect' =>  url()->previous(),
            'node' => $node,
            'resource' => $resource,
        ]);
    }

    public function store(StoreResourceRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $fileUrl = null;

        if ($request->hasFile('file')) {
            $path    = $request->file('file')->store("resources/{$validated['resource_type']}s", 'public');
            $fileUrl = Storage::url($path);
            $validated['file_url'] = $fileUrl;
        }

        $resource = Resource::create($validated);
        Cache::forget("node_resources_{$resource->node_id}");
        $this->clearResourcePageCache($resource->node_id);

        $redirect = $validated['redirect'] ?? explode('/resources', url()->previous())[0];

        return redirect($redirect)->with('success', 'Resource created successfully.');
    }


    public function update(UpdateResourceRequest $request, Resource $resource)
    {

        $validated = $request->validated();

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($resource->file_url) {
                $oldPath = str_replace('/storage/', '', parse_url($resource->file_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $type    = $validated['resource_type'] ?? $resource->resource_type;
            $path    = $request->file('file')->store("resources/{$type}s", 'public');
            $validated['file_url'] = Storage::url($path);
        }

        $resource->update($validated);
        Cache::forget("resource_{$resource->id}");
        Cache::forget("node_resources_{$resource->node_id}");

        $redirect = $validated['redirect'] ?? "/admin/subjects";

        return redirect($redirect)->with('success', 'Resource updated successfully.');
    }

    public function destroy(Resource $resource)
    {
        if ($resource->file_url) {
            $oldPath = str_replace('/storage/', '', parse_url($resource->file_url, PHP_URL_PATH));
            Storage::disk('public')->delete($oldPath);
        }

        $resource->delete();
        $this->clearResourcePageCache($resource->node_id);
        Cache::forget("resource_{$resource->id}");
        Cache::forget("node_resources_{$resource->node_id}");

        return redirect()->back()->with('success', 'Resource deleted successfully.');
    }
    private function clearResourcePageCache(int $nodeId): void
    {
        $ids = Resource::where('node_id', $nodeId)->pluck('id')->toArray();

        foreach ($ids as $id) {
            Cache::forget("resource_{$id}");
        }
    }
    function createBulkImages(Request $request)
    {
        $redirect = $request->input('redirect', url()->previous());
        $node = Node::findOrFail($request->node_id);


        return Inertia::render('admin/resources/BulkImageCreate', [
            'redirect' => $redirect,
            'node' => $node
        ]);
    }

    function storeBulkImages(BulkImageStoreRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            foreach ($request->file('files') as $index => $file) {

                $validated['title'] = $validated['custom_titles'][$index];
                $validated['file_url'] = Storage::url($file->store('resources/images', 'public'));
                $validated['user_id'] = Auth::id();
                $validated['resource_type'] = 'image';

                Resource::create($validated);
            }
        });

        Cache::forget("node_resources_{$validated['node_id']}");
        $this->clearResourcePageCache($validated['node_id']);

        return redirect($validated['redirect'])->with('success', 'Bulk images uploaded successfully.');
    }

    function createBulkVideos(Request $request)
    {
        $redirect = $request->input('redirect', url()->previous());
        $node = Node::findOrFail($request->node_id);


        return Inertia::render('admin/resources/BulkVideoCreate', [
            'redirect' => $redirect,
            'node' => $node
        ]);
    }


    function storeBulkVideos(BulkVideoStoreRequest $request)
    {
        $validated = $request->validated();

        $playlistUrl = $validated['playlist_url'];

        // Extract playlist ID
        parse_str(parse_url($playlistUrl, PHP_URL_QUERY), $query);

        $playlistId = $query['list'] ?? null;

        if (!$playlistId) {
            return back()->withErrors('Invalid YouTube playlist URL');
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

            if (!$response->successful()) {
                return back()->withErrors('Unable to fetch YouTube playlist.');
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
            }

            if ($validated['naming_strategy'] === 'serial') {
                $video['title'] = str_pad(
                    $request->start_number + $index,
                    2,
                    '0',
                    STR_PAD_LEFT
                );
            }

            if ($validated['naming_strategy'] === 'prefix') {
                $number = str_pad(
                    $validated['start_number'] + $index,
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $video['title'] = "{$validated['naming_prefix']} - {$number}";
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
                    'file_url' => $finalUrl,
                    'content' => $finalUrl,
                ]);
            }
        });

        return redirect($validated['redirect'])->with('success', 'Bulk Videos uploaded successfully.');
    }
}

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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

        $user = Auth::user();
        $isDirect = $user->can('approve resources') || $user->is_verified;
        $validated['status'] = $isDirect ? 'approved' : 'pending';
        if ($isDirect) {
            $validated['reviewed_by'] = $user->id;
            $validated['reviewed_at'] = now();
        }

        Resource::create($validated);

        $message = $isDirect
            ? 'Resource created successfully.'
            : 'Resource submitted and is pending review.';

        return back()->with('success', $message);
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
        $user = Auth::user();
        $isDirect = $user->can('approve resources') || $user->is_verified;
        $status = $isDirect ? 'approved' : 'pending';
        $reviewedBy = $isDirect ? $user->id : null;
        $reviewedAt = $isDirect ? now() : null;

        DB::transaction(function () use ($request, $validated, $status, $reviewedBy, $reviewedAt) {
            foreach ($request->file('files') as $index => $file) {

                $validated['title'] = $validated['custom_titles'][$index];
                $validated['file_path'] = $file->store('resources/images');
                $validated['user_id'] = Auth::id();
                $validated['resource_type'] = 'image';
                $validated['status'] = $status;
                $validated['reviewed_by'] = $reviewedBy;
                $validated['reviewed_at'] = $reviewedAt;

                Resource::create($validated);
            }
        });

        $message = $isDirect
            ? 'Images uploaded successfully.'
            : 'Images submitted and are pending review.';

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
        $user = Auth::user();
        $isDirect = $user->can('approve resources') || $user->is_verified;
        $status = $isDirect ? 'approved' : 'pending';
        $reviewedBy = $isDirect ? $user->id : null;
        $reviewedAt = $isDirect ? now() : null;

        DB::transaction(function () use ($videos, $validated, $userId, $status, $reviewedBy, $reviewedAt) {
            foreach ($videos as $video) {
                $finalUrl = "https://www.youtube.com/watch?v={$video['video_id']}";

                Resource::create([
                    'user_id' => $userId,
                    'node_id' => $validated['node_id'],
                    'title' => $video['title'],
                    'resource_type' => 'video',
                    'external_url' => $finalUrl,
                    'status' => $status,
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => $reviewedAt,
                ]);
            }
        });

        $message = $isDirect
            ? 'YouTube playlist imported successfully.'
            : 'YouTube playlist imported and is pending review.';

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

    public function pending(Request $request)
    {
        $resources = Resource::where('status', 'pending')
            ->with([
                'user:id,name,username,image_path,is_verified',
                'node.subject',
            ])
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/resources/Pending', [
            'resources' => $resources,
        ]);
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:resources,id'],
        ]);

        $resources = Resource::whereIn('id', $validated['ids'])
            ->where('status', 'pending')
            ->get();

        $userId = Auth::id();
        $now = now();

        foreach ($resources as $resource) {
            $resource->update([
                'status' => 'approved',
                'reviewed_by' => $userId,
                'reviewed_at' => $now,
            ]);
        }

        $count = $resources->count();

        return back()->with('success', "{$count} resource(s) approved successfully.");
    }

    public function reject(Request $request, Resource $resource)
    {
        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = Auth::id();
        $now = now();

        $resource->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'reviewed_by' => $userId,
            'reviewed_at' => $now,
        ]);

        return back()->with('success', 'Resource rejected.');
    }
}

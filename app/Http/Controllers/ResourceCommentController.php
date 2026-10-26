<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceComment;
use App\Models\User;
use App\Notifications\ResourceCommentMentionNotification;
use App\Notifications\ResourceCommentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ResourceCommentController extends Controller
{
    public function store(Request $request, Resource $resource)
    {
        $user = auth()->user();

        // Check if user is suspended from community interactions
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
            // Flatten comments to 1-level deep hierarchy:
            // If replying to an existing reply, automatically attach to the root comment ($parent->parent_id)
            // instead of creating infinitely nested deep sub-threads.
            $parentId = $parent?->parent_id ?: $parent?->id;
        }

        $comment = $resource->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => trim($validated['content']),
        ]);

        $mentionedUserIds = [];

        // Parse @username mentions in the comment body and dispatch notification to up to 5 mentioned users
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->content, $matches)) {
            $mentionedUsernames = array_unique(array_filter($matches[1], fn ($u) => strcasecmp($u, $user->username) !== 0));
            if (! empty($mentionedUsernames)) {
                $mentionedUsers = User::whereIn('username', array_slice($mentionedUsernames, 0, 5))
                    ->select(['id', 'name', 'username', 'email'])
                    ->get();

                if ($mentionedUsers->isNotEmpty()) {
                    Notification::send($mentionedUsers, new ResourceCommentMentionNotification($resource, $comment, $user));
                    $mentionedUserIds = $mentionedUsers->pluck('id')->all();
                }
            }
        }

        // Notify the resource author if the commenter is someone else and hasn't already been notified via @mention
        if ($resource->user_id !== $user->id && ! in_array($resource->user_id, $mentionedUserIds, true)) {
            $resource->user?->notify(new ResourceCommentNotification($resource, $comment));
        }

        return back()->with('success', 'Comment posted successfully.');
    }

    public function destroy(ResourceComment $comment)
    {
        // Enforce deletion permission: only comment author or moderators can delete comments
        abort_unless(auth()->id() === $comment->user_id || auth()->user()?->can('moderate resources'), 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }
}

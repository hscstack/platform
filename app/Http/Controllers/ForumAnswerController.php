<?php

namespace App\Http\Controllers;

use App\Mail\ForumNotificationMail;
use App\Models\AppSetting;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Report;
use App\Rules\CleanText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ForumAnswerController extends Controller
{
    public function store(Request $request, ForumPost $post): RedirectResponse
    {
        $user = auth()->user();

        // Check if discussion is locked
        if ($post->is_locked && (! $user || ! $user->can('manage forums'))) {
            return back()->with('error', 'This discussion has been locked by a moderator. New replies are disabled.');
        }

        // Check if global forum commenting is enabled
        $isCommentsEnabled = (bool) AppSetting::get('forum_comments_enabled', true);
        if (! $isCommentsEnabled && (! $user || ! $user->can('view admin'))) {
            $reason = AppSetting::get('forum_disabled_reason', 'Submitting comments is temporarily paused.');

            return back()->with('error', $reason ?: 'Submitting comments is temporarily paused.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:10000', new CleanText],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'parent_id' => ['nullable', 'exists:forum_answers,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parent = ForumAnswer::where('forum_post_id', $post->id)->findOrFail($parentId);
            // Enforce max 1-level nesting by flattening any nested reply to the root parent
            $parentId = $parent->parent_id ?: $parent->id;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('forum/answers');
        }

        $answer = $post->answers()->create([
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'body' => $validated['body'],
            'image_path' => $imagePath,
        ]);

        $post->increment('answers_count');

        // Send mail notification to post author only (if commenter is not the author)
        $author = $post->user;
        $currentUser = auth()->user();
        if ($author && $currentUser && $author->id !== $currentUser->id && $author->email && $author->receive_emails !== false) {
            Mail::to($author->email)->queue(ForumNotificationMail::forAnswer($post, $answer, $author, $currentUser));
        }

        return back()->with('success', 'Answer posted!');
    }

    public function destroy(ForumAnswer $answer): RedirectResponse
    {
        abort_unless(auth()->id() === $answer->user_id || auth()->user()?->can('manage forums'), 403);

        $post = $answer->post;
        $replies = $answer->replies;
        $repliesCount = $replies->count();

        if ($answer->image_path) {
            Storage::delete($answer->image_path);
        }

        foreach ($replies as $reply) {
            if ($reply->image_path) {
                Storage::delete($reply->image_path);
            }
        }

        $answer->delete();

        if ($post) {
            $post->decrement('answers_count', 1 + $repliesCount);
        }

        return back()->with('success', 'Deleted.');
    }

    public function report(Request $request, ForumAnswer $answer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $alreadyReported = Report::where('reporter_id', $user->id)
            ->where('reportable_type', ForumAnswer::class)
            ->where('reportable_id', $answer->id)
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'message' => 'You have already reported this answer.',
            ], 422);
        }

        $author = $answer->user;

        $report = Report::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $author?->id,
            'reported_user_name' => $author?->name,
            'reported_user_username' => $author?->username,
            'reportable_type' => ForumAnswer::class,
            'reportable_id' => $answer->id,
            'content_snapshot' => $answer->body,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Answer reported successfully. Our moderation team will review it.',
            'report_id' => $report->id,
        ], 201);
    }
}

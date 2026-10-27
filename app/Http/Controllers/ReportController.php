<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Models\ChatMessage;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Report;
use App\Models\ResourceComment;
use App\Models\User;
use App\Notifications\ChatReportNotification;
use App\Notifications\ForumReportNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ReportController extends Controller
{
    /**
     * Unified public reporting endpoint for all content types.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => ['required', 'string', 'in:chat_message,forum_post,forum_answer,resource_comment,blog_comment'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $reporter = $request->user();

        [$modelClass, $item, $reportedUser, $snapshot, $sentAt] = $this->resolveReportable(
            $validated['reportable_type'],
            $validated['reportable_id']
        );

        if (! $item) {
            return response()->json(['error' => 'The reported item does not exist.'], 404);
        }

        if ($reportedUser && $reportedUser->id === $reporter->id) {
            return response()->json(['error' => 'You cannot report your own content.'], 422);
        }

        $existing = Report::where('reporter_id', $reporter->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $item->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already reported this item. Our moderation team is reviewing it.',
            ], 200);
        }

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reportedUser?->id,
            'reported_user_name' => $reportedUser?->name,
            'reported_user_username' => $reportedUser?->username,
            'reportable_type' => $modelClass,
            'reportable_id' => $item->id,
            'content_snapshot' => $snapshot,
            'message_sent_at' => $sentAt,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'pending',
        ]);

        // Send notifications to report moderators
        $this->notifyModerators($report, $reporter);

        return response()->json([
            'message' => 'Thank you. The report has been received and will be reviewed by our moderation team.',
        ], 201);
    }

    private function resolveReportable(string $type, int $id): array
    {
        return match ($type) {
            'chat_message' => (function () use ($id) {
                $msg = ChatMessage::with('user')->find($id);

                return [
                    ChatMessage::class,
                    $msg,
                    $msg?->user,
                    $msg?->content ?? '',
                    $msg?->created_at,
                ];
            })(),
            'forum_post' => (function () use ($id) {
                $post = ForumPost::with('user')->find($id);

                return [
                    ForumPost::class,
                    $post,
                    $post?->user,
                    $post?->body ?? '',
                    $post?->created_at,
                ];
            })(),
            'forum_answer' => (function () use ($id) {
                $answer = ForumAnswer::with('user')->find($id);

                return [
                    ForumAnswer::class,
                    $answer,
                    $answer?->user,
                    $answer?->body ?? '',
                    $answer?->created_at,
                ];
            })(),
            'resource_comment' => (function () use ($id) {
                $comm = ResourceComment::with('user')->find($id);

                return [
                    ResourceComment::class,
                    $comm,
                    $comm?->user,
                    $comm?->content ?? '',
                    $comm?->created_at,
                ];
            })(),
            'blog_comment' => (function () use ($id) {
                $comm = BlogComment::with('user')->find($id);

                return [
                    BlogComment::class,
                    $comm,
                    $comm?->user,
                    $comm?->content ?? '',
                    $comm?->created_at,
                ];
            })(),
            default => [null, null, null, '', null],
        };
    }

    private function notifyModerators(Report $report, User $reporter): void
    {
        $moderators = User::permission('manage reports')->select(['id', 'name', 'email'])->get();
        if ($moderators->isEmpty()) {
            // Fallback: notify users with manage chat or manage forums if manage reports not yet assigned
            $moderators = User::permission(['manage chat', 'manage forums'])->select(['id', 'name', 'email'])->get();
        }

        if ($moderators->isNotEmpty()) {
            if ($report->reportable_type === ChatMessage::class) {
                Notification::send($moderators, new ChatReportNotification($report, $reporter));
            } elseif (in_array($report->reportable_type, [ForumPost::class, ForumAnswer::class], true)) {
                Notification::send($moderators, new ForumReportNotification($report, $reporter));
            }
        }
    }
}

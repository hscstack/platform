<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageReacted;
use App\Events\ChatMessageSent;
use App\Models\AppSetting;
use App\Models\ChatMessage;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ChatMentionNotification;
use App\Notifications\ChatReportNotification;
use App\Services\ChatProfanityFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get Chat Config Status
        $isEnabled = AppSetting::get('global_chat_enabled', true);
        $audience = AppSetting::get('global_chat_audience', 'verified_members'); // 'verified_members', 'all', 'disabled'
        $cooldownSeconds = (int) AppSetting::get('global_chat_cooldown_seconds', 30);
        $maxMessages = (int) AppSetting::get('global_chat_max_messages', 200);
        $maxLength = (int) AppSetting::get('global_chat_max_length', 280);

        // Determine if user can post
        $canPost = false;
        $reason = null;

        if (! $isEnabled || $audience === 'disabled') {
            $customReason = trim((string) AppSetting::get('global_chat_disabled_reason', ''));
            $reason = ! empty($customReason) ? $customReason : 'Global chat is currently disabled for maintenance.';
        } elseif (! $user) {
            $reason = 'Please sign in to join the conversation.';
        } elseif ($user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();
            $reason = "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).";
        } elseif ($audience === 'verified_members') {
            if ($user->is_verified || $user->can('view admin')) {
                $canPost = true;
            } else {
                $reason = 'Global chat is currently in beta for verified members and contributors.';
            }
        } elseif ($audience === 'all') {
            $canPost = true;
        }

        // Fetch last X messages in chronological order
        $messages = ChatMessage::with([
            'user:id,name,username,image_path,institution,is_verified',
            'user.roles:id,name',
            'reactions.user:id,name,username,image_path,institution,is_verified',
            'reactions.user.roles:id,name',
        ])
            ->latest('id')
            ->take($maxMessages)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $msg) => [
                'id' => $msg->id,
                'content' => $msg->deleted_at ? 'This message was deleted by a moderator.' : $msg->content,
                'is_deleted' => $msg->deleted_at !== null,
                'deleted_at' => $msg->deleted_at?->toIso8601String(),
                'reply_to_id' => $msg->deleted_at ? null : $msg->reply_to_id,
                'reply_to_content' => $msg->deleted_at ? null : $msg->reply_to_content,
                'reactions' => $msg->getFormattedReactions($user?->id),
                'created_at' => $msg->created_at->toIso8601String(),
                'user' => [
                    'id' => $msg->user->id,
                    'name' => $msg->user->name,
                    'username' => $msg->user->username,
                    'image_url' => $msg->user->image_url,
                    'institution' => $msg->user->institution,
                    'is_verified' => $msg->user->is_verified,
                    'roles' => $msg->user->roles->pluck('name')->toArray(),
                ],
            ]);

        $allowedEmojis = (array) AppSetting::get('global_chat_allowed_emojis', ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏']);

        $channelName = app()->environment('production') ? 'global-chat' : app()->environment().'.global-chat';

        if (! $request->wantsJson() && ! $request->is('api/*')) {
            return Inertia::render('Chat/Index', [
                'chatState' => [
                    'enabled' => (bool) $isEnabled,
                    'audience' => $audience,
                    'cooldown_seconds' => $cooldownSeconds,
                    'max_messages' => $maxMessages,
                    'max_length' => $maxLength,
                    'can_post' => $canPost,
                    'reason' => $reason,
                    'can_delete' => (bool) $user?->can('manage chat'),
                    'reaction_emojis' => $allowedEmojis,
                    'messages' => $messages,
                    'channel_name' => $channelName,
                    'pusher_key' => config('broadcasting.connections.pusher.key'),
                    'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster', 'ap2'),
                ],
            ]);
        }

        return response()->json([
            'enabled' => (bool) $isEnabled,
            'audience' => $audience,
            'cooldown_seconds' => $cooldownSeconds,
            'max_messages' => $maxMessages,
            'max_length' => $maxLength,
            'can_post' => $canPost,
            'reason' => $reason,
            'can_delete' => (bool) $user?->can('manage chat'),
            'reaction_emojis' => $allowedEmojis,
            'messages' => $messages,
            'channel_name' => $channelName,
            'pusher_key' => config('broadcasting.connections.pusher.key'),
            'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster', 'ap2'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');

        $isEnabled = AppSetting::get('global_chat_enabled', true);
        $audience = AppSetting::get('global_chat_audience', 'verified_members');
        $cooldownSeconds = (int) AppSetting::get('global_chat_cooldown_seconds', 30);
        $maxMessages = (int) AppSetting::get('global_chat_max_messages', 200);
        $maxLength = (int) AppSetting::get('global_chat_max_length', 280);

        // Check if chat is enabled
        if (! $isEnabled || $audience === 'disabled') {
            $customReason = trim((string) AppSetting::get('global_chat_disabled_reason', ''));
            $msg = ! empty($customReason) ? $customReason : 'Global chat is currently disabled.';

            return response()->json(['message' => $msg], 403);
        }

        // Check if user is banned
        if ($user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return response()->json([
                'message' => "You are suspended from sending messages until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).",
            ], 403);
        }

        // Check audience permission
        if ($audience === 'verified_members' && ! $user->is_verified && ! $user->can('view admin')) {
            return response()->json(['message' => 'Global chat is currently restricted to verified members.'], 403);
        }

        // Configurable rate limiter per user (bypass for admins/staff)
        $rateLimitKey = "chat-send:{$user->id}";
        if (! $user->can('view admin') && $cooldownSeconds > 0 && RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'message' => "Please wait {$seconds} seconds before sending another message.",
                'retry_after' => $seconds,
            ], 429);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', "max:{$maxLength}"],
            'reply_to_id' => ['nullable', 'integer'],
        ]);

        $content = trim($validated['content']);

        // Check for abusive / prohibited language
        if (ChatProfanityFilter::hasProfanity($content)) {
            $content = '[Message hidden for inappropriate language]';
        }

        // Prevent duplicate message sent twice in a streak by the same user
        $lastMessage = ChatMessage::where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($lastMessage && mb_strtolower($lastMessage->content) === mb_strtolower($content)) {
            return response()->json([
                'message' => 'You cannot send the exact same message twice in a row.',
            ], 422);
        }

        $replyToId = $validated['reply_to_id'] ?? null;
        $replyToContent = null;

        if ($replyToId) {
            $parentMessage = ChatMessage::find($replyToId);
            if ($parentMessage) {
                $replyToContent = $parentMessage->deleted_at
                    ? 'This message was deleted by a moderator.'
                    : Str::limit($parentMessage->content, 97, '...');
            } else {
                $replyToId = null;
            }
        }

        $message = ChatMessage::create([
            'user_id' => $user->id,
            'content' => $content,
            'reply_to_id' => $replyToId,
            'reply_to_content' => $replyToContent,
        ]);

        // Record rate limit for configured cooldown seconds
        if ($cooldownSeconds > 0) {
            RateLimiter::hit($rateLimitKey, $cooldownSeconds);
        }

        // Keep rolling buffer of latest X messages in DB
        ChatMessage::pruneOldMessages($maxMessages);

        // Notify mentioned users via in-app notification
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) {
            $mentionedUsernames = array_unique(array_filter($matches[1], fn ($u) => strcasecmp($u, $user->username) !== 0));
            if (! empty($mentionedUsernames)) {
                $mentionedUsers = User::whereIn('username', array_slice($mentionedUsernames, 0, 5))
                    ->select(['id', 'name', 'username', 'email'])
                    ->get();

                if ($mentionedUsers->isNotEmpty()) {
                    Notification::send($mentionedUsers, new ChatMentionNotification($message, $user));
                }
            }
        }

        // Broadcast to Pusher Channels
        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            // Log without failing response
        }

        $message->loadMissing(['user:id,name,username,image_path,institution,is_verified', 'user.roles:id,name']);

        return response()->json([
            'id' => $message->id,
            'content' => $message->deleted_at ? 'This message was deleted by a moderator.' : $message->content,
            'is_deleted' => $message->deleted_at !== null,
            'deleted_at' => $message->deleted_at?->toIso8601String(),
            'reply_to_id' => $message->reply_to_id,
            'reply_to_content' => $message->reply_to_content,
            'reactions' => [],
            'created_at' => $message->created_at->toIso8601String(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'username' => $message->user->username,
                'image_url' => $message->user->image_url,
                'institution' => $message->user->institution,
                'is_verified' => $message->user->is_verified,
                'roles' => $message->user->roles->pluck('name')->toArray(),
            ],
        ], 201);
    }

    public function destroy(Request $request, ChatMessage $message)
    {
        $user = $request->user();
        abort_unless($user, 401);

        // Only staff with manage chat permission can delete messages
        if (! $user->can('manage chat')) {
            abort(403, 'Unauthorized');
        }

        $deletedAt = now();
        $message->update([
            'content' => '',
            'reply_to_id' => null,
            'reply_to_content' => null,
            'deleted_at' => $deletedAt,
        ]);

        try {
            broadcast(new ChatMessageDeleted($message->id, $deletedAt->toIso8601String()))->toOthers();
        } catch (\Throwable $e) {
            // Ignore
        }

        return response()->json([
            'success' => true,
            'deleted_at' => $deletedAt->toIso8601String(),
        ]);
    }

    public function report(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');

        $validated = $request->validate([
            'message_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
            'reported_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reported_user_name' => ['nullable', 'string', 'max:255'],
            'reported_user_username' => ['nullable', 'string', 'max:255'],
            'message_content' => ['required', 'string', 'max:1000'],
            'message_sent_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Resolve the target ChatMessage server-side when an ID is provided so that
        $targetMessageId = $validated['message_id'] ?? null;
        $contentSnapshot = $validated['message_content'];
        $messageSentAt = $validated['message_sent_at'] ?? null;
        $reportedUserId = $validated['reported_user_id'] ?? null;
        $reportedUserName = $validated['reported_user_name'] ?? null;
        $reportedUserUsername = $validated['reported_user_username'] ?? null;

        if ($targetMessageId) {
            $chatMessage = ChatMessage::find($targetMessageId);
            if ($chatMessage) {
                $contentSnapshot = $chatMessage->content;
                $messageSentAt = $chatMessage->created_at;
                if ($chatMessage->user_id) {
                    $msgUser = $chatMessage->user;
                    $reportedUserId = $chatMessage->user_id;
                    $reportedUserName = $msgUser?->name;
                    $reportedUserUsername = $msgUser?->username;
                }
            }
        }

        // Prevent duplicate reporting of the same message by the same user
        $alreadyReported = Report::where('reporter_id', $user->id)
            ->where('reportable_type', ChatMessage::class)
            ->where(function ($q) use ($targetMessageId, $contentSnapshot) {
                if ($targetMessageId) {
                    $q->where('reportable_id', $targetMessageId);
                } else {
                    $q->where('content_snapshot', $contentSnapshot);
                }
            })
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'message' => 'You have already reported this message.',
            ], 422);
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $reportedUserId,
            'reported_user_name' => $reportedUserName,
            'reported_user_username' => $reportedUserUsername,
            'reportable_type' => ChatMessage::class,
            'reportable_id' => $targetMessageId,
            'content_snapshot' => $contentSnapshot,
            'message_sent_at' => $messageSentAt,
            'reason' => $validated['reason'] ?? 'Inappropriate message',
            'status' => 'pending',
        ]);

        $moderators = User::permission('manage chat')->select(['id', 'name', 'email'])->get();
        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new ChatReportNotification($report, $user));
        }

        // Dynamic Auto-ban logic on X reports
        $autoBanEnabled = (bool) AppSetting::get('global_chat_auto_ban_enabled', true);
        $threshold = (int) AppSetting::get('global_chat_auto_ban_reports_threshold', 5);
        $durationMinutes = (int) AppSetting::get('global_chat_auto_ban_duration_minutes', 0);
        if ($durationMinutes <= 0) {
            $durationMinutes = (int) AppSetting::get('global_chat_auto_ban_duration_hours', 24) * 60;
        }

        if ($autoBanEnabled && ! empty($reportedUserId) && $threshold > 0) {
            $reportedUser = User::find($reportedUserId);
            if ($reportedUser && ! $reportedUser->can('view admin')) {
                // Count reports keyed to the specific resolved message (or content snapshot)
                $totalReportsForMessage = Report::where('reported_user_id', $reportedUser->id)
                    ->where('reportable_type', ChatMessage::class)
                    ->where(function ($q) use ($targetMessageId, $contentSnapshot) {
                        if ($targetMessageId) {
                            $q->where('reportable_id', $targetMessageId);
                        } else {
                            $q->where('content_snapshot', $contentSnapshot);
                        }
                    })
                    ->count();

                if ($totalReportsForMessage >= $threshold) {
                    $wasAlreadyBanned = $reportedUser->isBanned();
                    $banUntil = now()->addMinutes(max(1, $durationMinutes));
                    $reportedUser->update([
                        'banned_until' => $banUntil,
                    ]);

                    if (! $wasAlreadyBanned) {
                        $durationText = $durationMinutes >= 1440 && $durationMinutes % 1440 === 0
                            ? ($durationMinutes / 1440).' day'.($durationMinutes / 1440 > 1 ? 's' : '')
                            : ($durationMinutes >= 60 && $durationMinutes % 60 === 0
                                ? ($durationMinutes / 60).' hour'.($durationMinutes / 60 > 1 ? 's' : '')
                                : "{$durationMinutes} minutes");

                        ChatMessage::sendBotMessage("System automatically suspended @{$reportedUser->username} from community participation for {$durationText} following {$totalReportsForMessage} community reports.");
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Message reported successfully. Our team will review it.',
            'report_id' => $report->id,
        ], 201);
    }

    public function toggleReaction(Request $request, ChatMessage $message)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');

        if ($user->isBanned()) {
            return response()->json([
                'message' => 'You cannot react to messages while suspended.',
            ], 403);
        }

        if ($message->isDeleted()) {
            return response()->json([
                'message' => 'Cannot react to deleted messages.',
            ], 422);
        }

        $validated = $request->validate([
            'emoji' => ['required', 'string', 'max:32'],
        ]);

        $emoji = $validated['emoji'];

        // Allowed reaction emojis from settings
        $allowedEmojis = (array) AppSetting::get('global_chat_allowed_emojis', ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏']);
        if (! in_array($emoji, $allowedEmojis, true)) {
            return response()->json([
                'message' => 'This reaction emoji is not enabled.',
            ], 422);
        }

        $existingReaction = $message->reactions()
            ->where('user_id', $user->id)
            ->first();

        if ($existingReaction && $existingReaction->emoji === $emoji) {
            // Same emoji clicked again -> toggle off / remove
            $existingReaction->delete();
        } else {
            // Different emoji or no previous reaction -> remove existing and add new
            if ($existingReaction) {
                $existingReaction->delete();
            }

            $message->reactions()->create([
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
        }

        $message->unsetRelation('reactions');
        $formattedReactions = $message->getFormattedReactions($user->id);
        $publicReactions = $message->getFormattedReactions(null);

        try {
            broadcast(new ChatMessageReacted($message->id, $publicReactions))->toOthers();
        } catch (\Throwable $e) {
            // Ignore
        }

        return response()->json([
            'success' => true,
            'reactions' => $formattedReactions,
        ]);
    }
}

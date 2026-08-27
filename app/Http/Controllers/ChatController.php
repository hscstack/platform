<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageSent;
use App\Models\AppSetting;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
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

        // Determine if user can post
        $canPost = false;
        $reason = null;

        if (! $isEnabled || $audience === 'disabled') {
            $reason = 'Global chat is currently disabled for maintenance.';
        } elseif (! $user) {
            $reason = 'Please sign in to join the conversation.';
        } elseif ($user->isChatBanned()) {
            $bannedUntilFormatted = $user->chat_banned_until->diffForHumans();
            $reason = "You are temporarily banned from chat until {$user->chat_banned_until->toDateTimeString()} ({$bannedUntilFormatted}).";
        } elseif ($audience === 'verified_members') {
            if ($user->is_verified || $user->can('view admin')) {
                $canPost = true;
            } else {
                $reason = 'Global chat is currently in beta for verified members and contributors.';
            }
        } elseif ($audience === 'all') {
            $canPost = true;
        }

        // Fetch last 200 messages in chronological order
        $messages = ChatMessage::with(['user:id,name,username,image_path,institution', 'user.roles:id,name'])
            ->latest('id')
            ->take(200)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $msg) => [
                'id' => $msg->id,
                'content' => $msg->content,
                'reply_to_id' => $msg->reply_to_id,
                'reply_to_content' => $msg->reply_to_content,
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

        if (! $request->wantsJson() && ! $request->is('api/*')) {
            return Inertia::render('Chat/Index', [
                'chatState' => [
                    'enabled' => (bool) $isEnabled,
                    'audience' => $audience,
                    'cooldown_seconds' => $cooldownSeconds,
                    'can_post' => $canPost,
                    'reason' => $reason,
                    'can_delete' => (bool) $user?->can('manage chat'),
                    'messages' => $messages,
                    'pusher_key' => config('broadcasting.connections.pusher.key'),
                    'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster', 'ap2'),
                ],
            ]);
        }

        return response()->json([
            'enabled' => (bool) $isEnabled,
            'audience' => $audience,
            'cooldown_seconds' => $cooldownSeconds,
            'can_post' => $canPost,
            'reason' => $reason,
            'can_delete' => (bool) $user?->can('manage chat'),
            'messages' => $messages,
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

        // Check if chat is enabled
        if (! $isEnabled || $audience === 'disabled') {
            return response()->json(['message' => 'Global chat is currently disabled.'], 403);
        }

        // Check if user is chat banned
        if ($user->isChatBanned()) {
            $bannedUntilFormatted = $user->chat_banned_until->diffForHumans();

            return response()->json([
                'message' => "You are banned from sending messages until {$user->chat_banned_until->toDateTimeString()} ({$bannedUntilFormatted}).",
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
            'content' => ['required', 'string', 'max:280'],
            'reply_to_id' => ['nullable', 'integer'],
        ]);

        $content = trim($validated['content']);
        $replyToId = $validated['reply_to_id'] ?? null;
        $replyToContent = null;

        if ($replyToId) {
            $parentMessage = ChatMessage::find($replyToId);
            if ($parentMessage) {
                $replyToContent = Str::limit($parentMessage->content, 97, '...');
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

        // Keep rolling buffer of latest 200 messages in DB
        ChatMessage::pruneOldMessages(200);

        // Broadcast to Pusher Channels
        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            // Log without failing response
        }

        $message->loadMissing(['user:id,name,username,image_path,institution', 'user.roles:id,name']);

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
            'reply_to_id' => $message->reply_to_id,
            'reply_to_content' => $message->reply_to_content,
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

        // Can delete if own message or has manage chat permission
        if ($message->user_id !== $user->id && ! $user->can('manage chat')) {
            abort(403, 'Unauthorized');
        }

        $messageId = $message->id;
        $message->delete();

        try {
            broadcast(new ChatMessageDeleted($messageId))->toOthers();
        } catch (\Throwable $e) {
            // Ignore
        }

        return response()->json(['success' => true]);
    }

    public function report(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');

        $validated = $request->validate([
            'reported_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reported_user_name' => ['nullable', 'string', 'max:255'],
            'reported_user_username' => ['nullable', 'string', 'max:255'],
            'message_content' => ['required', 'string', 'max:1000'],
            'message_sent_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Prevent duplicate reporting of the exact same message content by the same user
        $alreadyReported = \App\Models\ChatReport::where('reporter_id', $user->id)
            ->where('message_content', $validated['message_content'])
            ->where('reported_user_id', $validated['reported_user_id'] ?? null)
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'message' => 'You have already reported this message.',
            ], 422);
        }

        $report = \App\Models\ChatReport::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $validated['reported_user_id'] ?? null,
            'reported_user_name' => $validated['reported_user_name'] ?? null,
            'reported_user_username' => $validated['reported_user_username'] ?? null,
            'message_content' => $validated['message_content'],
            'message_sent_at' => $validated['message_sent_at'] ?? null,
            'reason' => $validated['reason'] ?? 'Inappropriate message',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Message reported successfully. Our team will review it.',
            'report_id' => $report->id,
        ], 201);
    }
}

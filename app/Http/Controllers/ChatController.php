<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageSent;
use App\Models\AppSetting;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get Chat Config Status
        $isEnabled = AppSetting::get('global_chat_enabled', true);
        $audience = AppSetting::get('global_chat_audience', 'verified_members'); // 'verified_members', 'all', 'disabled'

        // Determine if user can post
        $canPost = false;
        $reason = null;

        if (! $isEnabled || $audience === 'disabled') {
            $reason = 'Global chat is currently disabled for maintenance.';
        } elseif (! $user) {
            $reason = 'Please sign in to join the global student chat.';
        } elseif ($audience === 'verified_members') {
            if ($user->is_verified || $user->can('view admin')) {
                $canPost = true;
            } else {
                $reason = 'Global chat is currently open for verified members and contributors.';
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
                    'can_post' => $canPost,
                    'reason' => $reason,
                    'can_delete' => (bool) $user?->can('delete chat messages'),
                    'messages' => $messages,
                    'pusher_key' => config('broadcasting.connections.pusher.key'),
                    'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster', 'ap2'),
                ],
            ]);
        }

        return response()->json([
            'enabled' => (bool) $isEnabled,
            'audience' => $audience,
            'can_post' => $canPost,
            'reason' => $reason,
            'can_delete' => (bool) $user?->can('delete chat messages'),
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

        // Check if chat is enabled
        if (! $isEnabled || $audience === 'disabled') {
            return response()->json(['message' => 'Global chat is currently disabled.'], 403);
        }

        // Check audience permission
        if ($audience === 'verified_members' && ! $user->is_verified && ! $user->can('view admin')) {
            return response()->json(['message' => 'Global chat is currently restricted to verified members.'], 403);
        }

        // Strict 30-second rate limiter per user (bypass for admins/staff)
        $rateLimitKey = "chat-send:{$user->id}";
        if (! $user->can('view admin') && RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'message' => "Please wait {$seconds} seconds before sending another message.",
                'retry_after' => $seconds,
            ], 429);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:280'],
        ]);

        $content = trim($validated['content']);

        $message = ChatMessage::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        // Record rate limit for 30 seconds
        RateLimiter::hit($rateLimitKey, 30);

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

        // Can delete if own message or has delete chat messages permission
        if ($message->user_id !== $user->id && ! $user->can('delete chat messages')) {
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
}

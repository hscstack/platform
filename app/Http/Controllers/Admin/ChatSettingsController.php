<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChatSettingsUpdated;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChatSettingsController extends Controller
{
    public function edit()
    {
        $bannedWords = AppSetting::get('global_chat_banned_words', '');
        $bannedWordsText = is_array($bannedWords) ? implode(', ', $bannedWords) : (string) $bannedWords;
        $allowedEmojis = AppSetting::get('global_chat_allowed_emojis', ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏']);

        $durationMinutes = (int) AppSetting::get('global_chat_auto_ban_duration_minutes', 0);
        if ($durationMinutes <= 0) {
            $durationMinutes = (int) AppSetting::get('global_chat_auto_ban_duration_hours', 24) * 60;
        }

        return Inertia::render('admin/ChatSettings', [
            'settings' => [
                'enabled' => (bool) AppSetting::get('global_chat_enabled', true),
                'audience' => AppSetting::get('global_chat_audience', 'verified_members'),
                'disabled_reason' => (string) AppSetting::get('global_chat_disabled_reason', ''),
                'cooldown_seconds' => (int) AppSetting::get('global_chat_cooldown_seconds', 30),
                'max_messages' => (int) AppSetting::get('global_chat_max_messages', 200),
                'max_length' => (int) AppSetting::get('global_chat_max_length', 280),
                'profanity_filter_enabled' => (bool) AppSetting::get('global_chat_profanity_filter_enabled', true),
                'banned_words' => $bannedWordsText,
                'allowed_emojis' => (array) $allowedEmojis,
                'bot_username' => (string) AppSetting::get('global_chat_bot_username', 'hscstack'),
                'auto_ban_enabled' => (bool) AppSetting::get('global_chat_auto_ban_enabled', true),
                'auto_ban_threshold' => (int) AppSetting::get('global_chat_auto_ban_reports_threshold', 5),
                'auto_ban_duration_minutes' => $durationMinutes,
            ],
            'totalMessages' => ChatMessage::count(),
            'recentMessagesCount' => ChatMessage::where('created_at', '>=', now()->subHours(24))->count(),
            'pendingReportsCount' => Report::where('status', 'pending')->where('reportable_type', ChatMessage::class)->count(),
        ]);
    }

    public function reports()
    {
        $reports = Report::with(['reporter:id,name,username', 'reportedUser:id,name,username,chat_banned_until'])
            ->where('reportable_type', ChatMessage::class)
            ->latest('id')
            ->take(100)
            ->get()
            ->map(fn ($report) => [
                'id' => $report->id,
                'reporter_id' => $report->reporter_id,
                'reporter' => $report->reporter ? [
                    'id' => $report->reporter->id,
                    'name' => $report->reporter->name,
                    'username' => $report->reporter->username,
                ] : null,
                'reported_user_id' => $report->reported_user_id,
                'reported_user_name' => $report->reported_user_name,
                'reported_user_username' => $report->reported_user_username,
                'reported_user' => $report->reportedUser ? [
                    'id' => $report->reportedUser->id,
                    'name' => $report->reportedUser->name,
                    'username' => $report->reportedUser->username,
                    'chat_banned_until' => $report->reportedUser->chat_banned_until?->toIso8601String(),
                    'is_chat_banned' => $report->reportedUser->isChatBanned(),
                ] : null,
                'message_content' => $report->content_snapshot,
                'message_sent_at' => $report->message_sent_at?->toIso8601String(),
                'reason' => $report->reason,
                'status' => $report->status,
                'created_at' => $report->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/chat/Reports', [
            'reports' => $reports,
            'pendingCount' => Report::where('status', 'pending')->where('reportable_type', ChatMessage::class)->count(),
            'reviewedCount' => Report::where('status', 'reviewed')->where('reportable_type', ChatMessage::class)->count(),
            'dismissedCount' => Report::where('status', 'dismissed')->where('reportable_type', ChatMessage::class)->count(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'audience' => ['required', 'string', 'in:verified_members,all,disabled'],
            'disabled_reason' => ['nullable', 'string', 'max:255'],
            'cooldown_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'max_messages' => ['required', 'integer', 'min:20', 'max:1000'],
            'max_length' => ['required', 'integer', 'min:50', 'max:1000'],
            'profanity_filter_enabled' => ['required', 'boolean'],
            'banned_words' => ['nullable', 'string'],
            'allowed_emojis' => ['nullable', 'array'],
            'allowed_emojis.*' => ['required', 'string', 'max:32'],
            'bot_username' => ['nullable', 'string', 'max:50', 'exists:users,username'],
            'auto_ban_enabled' => ['required', 'boolean'],
            'auto_ban_threshold' => ['required', 'integer', 'min:1', 'max:50'],
            'auto_ban_duration_minutes' => ['required', 'integer', 'min:1', 'max:43200'],
        ]);

        $isEnabled = $validated['audience'] !== 'disabled';
        AppSetting::set('global_chat_enabled', $isEnabled, 'boolean');
        AppSetting::set('global_chat_audience', $validated['audience'], 'string');
        AppSetting::set('global_chat_disabled_reason', $validated['disabled_reason'] ?? '', 'string');
        AppSetting::set('global_chat_cooldown_seconds', $validated['cooldown_seconds'], 'integer');
        AppSetting::set('global_chat_max_messages', $validated['max_messages'], 'integer');
        AppSetting::set('global_chat_max_length', $validated['max_length'], 'integer');
        AppSetting::set('global_chat_profanity_filter_enabled', $validated['profanity_filter_enabled'], 'boolean');
        AppSetting::set('global_chat_banned_words', $validated['banned_words'] ?? '', 'string');
        AppSetting::set('global_chat_auto_ban_enabled', $validated['auto_ban_enabled'], 'boolean');
        AppSetting::set('global_chat_auto_ban_reports_threshold', $validated['auto_ban_threshold'], 'integer');
        AppSetting::set('global_chat_auto_ban_duration_minutes', $validated['auto_ban_duration_minutes'], 'integer');

        $emojis = ! empty($validated['allowed_emojis'])
            ? array_values(array_unique(array_filter($validated['allowed_emojis'])))
            : ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏'];
        AppSetting::set('global_chat_allowed_emojis', $emojis, 'json');

        if (isset($validated['bot_username']) && ! empty($validated['bot_username'])) {
            AppSetting::set('global_chat_bot_username', $validated['bot_username'], 'string');
        }

        // Immediately prune if current count exceeds new limit
        ChatMessage::pruneOldMessages($validated['max_messages']);

        // Broadcast settings update to global-chat channel
        try {
            broadcast(new ChatSettingsUpdated);
        } catch (\Throwable $e) {
            // Log without failing response
        }

        return back()->with('success', 'Global chat settings updated successfully.');
    }

    public function clearMessages()
    {
        DB::transaction(function () {
            ChatMessageReaction::query()->delete();
            ChatMessage::query()->delete();
        });

        return back()->with('success', 'All chat messages have been cleared.');
    }

    public function updateReportStatus(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,dismissed'],
        ]);

        $report->update(['status' => $validated['status']]);

        return back()->with('success', 'Report status updated.');
    }

    public function deleteReport(Report $report)
    {
        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }

    public function clearReports(Request $request)
    {
        $status = $request->input('status');

        if ($status && in_array($status, ['pending', 'reviewed', 'dismissed'], true)) {
            Report::where('reportable_type', ChatMessage::class)->where('status', $status)->delete();
            $message = "All {$status} reports have been deleted.";
        } else {
            Report::where('reportable_type', ChatMessage::class)->delete();
            $message = 'All report records have been deleted.';
        }

        return back()->with('success', $message);
    }

    public function updateUserBan(Request $request, User $user)
    {
        if ($user->can('view admin')) {
            return back()->with('error', 'Staff and Admin accounts cannot be banned from chat.');
        }

        $validated = $request->validate([
            'chat_banned_until' => ['nullable', 'date'],
        ]);

        $user->update([
            'chat_banned_until' => $validated['chat_banned_until'],
        ]);

        $moderator = $request->user();

        if ($user->isChatBanned()) {
            $durationText = $user->chat_banned_until->diffForHumans();
            ChatMessage::sendBotMessage("Moderator @{$moderator->username} banned @{$user->username} from chat until {$user->chat_banned_until->toDayDateTimeString()} ({$durationText}).");
            $message = "User @{$user->username} has been banned from chat until {$user->chat_banned_until->toDateTimeString()}.";
        } else {
            ChatMessage::sendBotMessage("Moderator @{$moderator->username} unbanned @{$user->username} from chat.");
            $message = "User @{$user->username} has been unbanned from chat.";
        }

        return back()->with('success', $message);
    }
}

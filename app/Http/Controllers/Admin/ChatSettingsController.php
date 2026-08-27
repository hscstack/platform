<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatSettingsController extends Controller
{
    public function edit()
    {
        return Inertia::render('admin/ChatSettings', [
            'settings' => [
                'enabled' => (bool) AppSetting::get('global_chat_enabled', true),
                'audience' => AppSetting::get('global_chat_audience', 'verified_members'),
                'cooldown_seconds' => (int) AppSetting::get('global_chat_cooldown_seconds', 30),
                'max_messages' => (int) AppSetting::get('global_chat_max_messages', 200),
            ],
            'totalMessages' => ChatMessage::count(),
            'recentMessagesCount' => ChatMessage::where('created_at', '>=', now()->subHours(24))->count(),
            'pendingReportsCount' => ChatReport::where('status', 'pending')->count(),
        ]);
    }

    public function reports()
    {
        $reports = ChatReport::with(['reporter:id,name,username', 'reportedUser:id,name,username,chat_banned_until'])
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
                'message_content' => $report->message_content,
                'message_sent_at' => $report->message_sent_at?->toIso8601String(),
                'reason' => $report->reason,
                'status' => $report->status,
                'created_at' => $report->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/chat/Reports', [
            'reports' => $reports,
            'pendingCount' => ChatReport::where('status', 'pending')->count(),
            'reviewedCount' => ChatReport::where('status', 'reviewed')->count(),
            'dismissedCount' => ChatReport::where('status', 'dismissed')->count(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'audience' => ['required', 'string', 'in:verified_members,all,disabled'],
            'cooldown_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'max_messages' => ['required', 'integer', 'min:20', 'max:1000'],
        ]);

        AppSetting::set('global_chat_enabled', $validated['enabled'], 'boolean');
        AppSetting::set('global_chat_audience', $validated['audience'], 'string');
        AppSetting::set('global_chat_cooldown_seconds', $validated['cooldown_seconds'], 'integer');
        AppSetting::set('global_chat_max_messages', $validated['max_messages'], 'integer');

        // Immediately prune if current count exceeds new limit
        ChatMessage::pruneOldMessages($validated['max_messages']);

        return back()->with('success', 'Global chat settings updated successfully.');
    }

    public function clearMessages()
    {
        ChatMessage::truncate();

        return back()->with('success', 'All chat messages have been cleared.');
    }

    public function updateReportStatus(Request $request, ChatReport $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,dismissed'],
        ]);

        $report->update(['status' => $validated['status']]);

        return back()->with('success', 'Report status updated.');
    }

    public function deleteReport(ChatReport $report)
    {
        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }
}

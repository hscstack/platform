<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ChatMessage;
use App\Models\Report;
use App\Models\User;
use App\Notifications\UserSuspensionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display a unified list of all community reports.
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'pending');
        $type = $request->query('type', 'all');

        $query = Report::with([
            'reporter:id,name,username,image_path',
            'reportedUser:id,name,username,image_path,is_verified,banned_until',
            'reviewer:id,name,username',
        ]);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $modelMap = [
                'chat' => 'App\Models\ChatMessage',
                'forum_post' => 'App\Models\ForumPost',
                'forum_answer' => 'App\Models\ForumAnswer',
                'resource_comment' => 'App\Models\ResourceComment',
                'blog_comment' => 'App\Models\BlogComment',
            ];

            if (isset($modelMap[$type])) {
                $query->where('reportable_type', $modelMap[$type]);
            }
        }

        $reports = $query->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
        ];

        $bannedWords = AppSetting::get('global_chat_banned_words', '');
        $bannedWordsText = is_array($bannedWords) ? implode(', ', $bannedWords) : (string) $bannedWords;

        return Inertia::render('admin/Reports', [
            'reports' => $reports,
            'counts' => $counts,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'safety_settings' => [
                'banned_words' => $bannedWordsText,
                'profanity_filter_enabled' => (bool) AppSetting::get('global_chat_profanity_filter_enabled', true),
            ],
        ]);
    }

    /**
     * Update the moderation status of a report.
     */
    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,resolved,dismissed'],
            'action_taken' => ['nullable', 'string', 'max:100'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'action_taken' => $validated['action_taken'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report status updated successfully.');
    }

    /**
     * Delete a single report.
     */
    public function destroy(Report $report): RedirectResponse
    {
        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }

    /**
     * Batch clear or resolve reports.
     */
    public function clear(Request $request): RedirectResponse
    {
        $status = $request->input('status');

        if ($status && in_array($status, ['pending', 'resolved', 'dismissed'], true)) {
            Report::where('status', $status)->delete();
            $message = ucfirst($status).' reports cleared successfully.';
        } else {
            Report::truncate();
            $message = 'All reports cleared successfully.';
        }

        return back()->with('success', $message);
    }

    /**
     * Centralized endpoint to suspend/ban or unban a user.
     * Only users with `manage reports` can access this action.
     */
    public function updateUserBan(Request $request, User $user): RedirectResponse
    {
        if ($user->is_verified) {
            return back()->with('error', 'Verified accounts cannot be suspended.');
        }

        $validated = $request->validate([
            'banned_until' => ['nullable', 'date'],
        ]);

        $bannedUntil = $validated['banned_until'] ?? null;

        $user->update([
            'banned_until' => $bannedUntil,
        ]);

        $moderator = $request->user();

        if ($user->isBanned()) {
            $durationText = $user->banned_until->diffForHumans();
            ChatMessage::sendBotMessage("Moderator @{$moderator->username} suspended @{$user->username} from community participation until {$user->banned_until->toDayDateTimeString()} ({$durationText}).");
            $user->notify(new UserSuspensionNotification(true, $user->banned_until->toDayDateTimeString()." ({$durationText})"));
            $message = "User @{$user->username} has been suspended until {$user->banned_until->toDateTimeString()}.";
        } else {
            ChatMessage::sendBotMessage("Moderator @{$moderator->username} removed suspension for @{$user->username}.");
            $user->notify(new UserSuspensionNotification(false));
            $message = "Suspension for @{$user->username} has been removed.";
        }

        return back()->with('success', $message);
    }

    /**
     * Update platform-wide banned keywords and profanity filter settings.
     */
    public function updateBannedWords(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'banned_words' => ['nullable', 'string'],
            'profanity_filter_enabled' => ['required', 'boolean'],
        ]);

        AppSetting::set('global_chat_banned_words', $validated['banned_words'] ?? '', 'string');
        AppSetting::set('global_chat_profanity_filter_enabled', $validated['profanity_filter_enabled'], 'boolean');

        return back()->with('success', 'Platform banned keywords updated successfully.');
    }
}

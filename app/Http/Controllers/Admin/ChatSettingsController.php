<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ChatMessage;
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
            ],
            'totalMessages' => ChatMessage::count(),
            'recentMessagesCount' => ChatMessage::where('created_at', '>=', now()->subHours(24))->count(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'audience' => ['required', 'string', 'in:verified_members,all,disabled'],
            'cooldown_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
        ]);

        AppSetting::set('global_chat_enabled', $validated['enabled'], 'boolean');
        AppSetting::set('global_chat_audience', $validated['audience'], 'string');
        AppSetting::set('global_chat_cooldown_seconds', $validated['cooldown_seconds'], 'integer');

        return back()->with('success', 'Global chat settings updated successfully.');
    }

    public function clearMessages()
    {
        ChatMessage::truncate();

        return back()->with('success', 'All chat messages have been cleared.');
    }
}

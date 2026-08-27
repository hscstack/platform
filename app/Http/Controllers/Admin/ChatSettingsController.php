<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class ChatSettingsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'audience' => ['required', 'string', 'in:verified_members,all,disabled'],
        ]);

        AppSetting::set('global_chat_enabled', $validated['enabled'], 'boolean');
        AppSetting::set('global_chat_audience', $validated['audience'], 'string');

        return back()->with('success', 'Global chat settings updated successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeerSettingsController extends Controller
{
    public const DEFAULT_PRESETS = [
        [
            'id' => 'syllabus',
            'icon' => '🔥',
            'message' => "That syllabus isn't going to finish itself! Open the books!",
        ],
        [
            'id' => 'phone_down',
            'icon' => '📱',
            'message' => 'Close the tabs, put the phone on DND, and start studying.',
        ],
    ];

    public static function getPresets(): array
    {
        return AppSetting::get('peer_poke_presets', self::DEFAULT_PRESETS) ?? self::DEFAULT_PRESETS;
    }

    public function edit()
    {
        $enabled = (bool) AppSetting::get('peer_poke_enabled', true);
        $cooldownMinutes = (int) AppSetting::get('peer_poke_cooldown_minutes', 360);
        $presets = self::getPresets();

        return Inertia::render('admin/PeerSettings', [
            'settings' => [
                'enabled' => $enabled,
                'cooldown_minutes' => $cooldownMinutes,
                'presets' => $presets,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'cooldown_minutes' => 'required|integer|min:1|max:10080',
            'presets' => 'required|array|min:1|max:20',
            'presets.*.id' => 'required|string|max:50',
            'presets.*.icon' => 'required|string|max:10',
            'presets.*.message' => 'required|string|max:200',
        ]);

        AppSetting::set('peer_poke_enabled', $validated['enabled'], 'boolean');
        AppSetting::set('peer_poke_cooldown_minutes', $validated['cooldown_minutes'], 'integer');
        AppSetting::set('peer_poke_presets', $validated['presets'], 'json');

        return redirect()->back()->with('success', 'Peer & Study Poke settings updated successfully.');
    }
}

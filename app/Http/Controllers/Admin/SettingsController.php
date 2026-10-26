<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\UpdateNoticeRequest;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
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

    public function platform()
    {
        return Inertia::render('admin/PlatformSettings', [
            'settings' => [
                'allow_registration' => (bool) AppSetting::get('allow_registration', true),
                'allow_resource_uploads' => (bool) AppSetting::get('allow_resource_uploads', true),
            ],
        ]);
    }

    public function updatePlatform(Request $request)
    {
        $validated = $request->validate([
            'allow_registration' => ['required', 'boolean'],
            'allow_resource_uploads' => ['required', 'boolean'],
        ]);

        AppSetting::set('allow_registration', $validated['allow_registration'], 'boolean');
        AppSetting::set('allow_resource_uploads', $validated['allow_resource_uploads'], 'boolean');

        return back()->with('success', 'Platform settings updated successfully.');
    }

    public function notice()
    {
        $rawNotice = AppSetting::get('site_notice');
        $notice = is_array($rawNotice) ? $rawNotice : (json_decode($rawNotice ?? '', true) ?: [
            'title' => null,
            'message' => null,
            'image' => null,
            'video_url' => null,
            'show_button' => false,
            'button_title' => null,
            'button_link' => null,
            'is_active' => false,
        ]);

        if (! empty($notice['image'])) {
            $notice['image'] = str($notice['image'])->startsWith(['http://', 'https://'])
                ? $notice['image']
                : Storage::url($notice['image']);
        }

        return Inertia::render('admin/NoticeEdit', [
            'notice' => $notice,
        ]);
    }

    public function updateNotice(UpdateNoticeRequest $request)
    {
        $rawNotice = AppSetting::get('site_notice');
        $existing = is_array($rawNotice) ? $rawNotice : (json_decode($rawNotice ?? '', true) ?: []);

        $data = $request->validated();

        if ($request->boolean('remove_image')) {
            $rawImage = $existing['image'] ?? null;
            if ($rawImage && ! str($rawImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($rawImage);
            }
            $existing['image'] = null;
        }

        if ($request->hasFile('image')) {
            $rawImage = $existing['image'] ?? null;
            if ($rawImage && ! str($rawImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($rawImage);
            }

            $path = $request->file('image')->store('notices');
            $existing['image'] = $path;
        }

        $existing['title'] = $data['title'] ?? null;
        $existing['message'] = $data['message'] ?? null;
        $existing['video_url'] = $data['video_url'] ?? null;
        $existing['show_button'] = (bool) ($data['show_button'] ?? false);
        $existing['button_title'] = $data['button_title'] ?? null;
        $existing['button_link'] = $data['button_link'] ?? null;
        $existing['is_active'] = (bool) ($data['is_active'] ?? false);
        $existing['updated_at'] = now()->toDateTimeString();

        AppSetting::set('site_notice', json_encode($existing));
        Cache::forget('active_notice_for_display');

        return redirect()->route('admin.notice.edit')->with('success', 'Notice updated.');
    }

    public function peers()
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

    public function updatePeers(Request $request)
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

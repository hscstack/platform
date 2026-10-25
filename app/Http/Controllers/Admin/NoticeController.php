<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\UpdateNoticeRequest;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function edit()
    {
        // Fetch notice config from AppSetting key 'site_notice'.
        // If not found or empty, initialize default structured notice dictionary.
        $rawNotice = AppSetting::get('site_notice');
        $notice = is_array($rawNotice) ? $rawNotice : (json_decode($rawNotice ?? '', true) ?: [
            'title' => null,
            'message' => null,
            'image' => null,
            'show_button' => false,
            'button_title' => null,
            'button_link' => null,
            'is_active' => false,
        ]);

        // If a relative disk image path is set, resolve it to public storage URL for admin preview
        if (! empty($notice['image'])) {
            $notice['image'] = str($notice['image'])->startsWith(['http://', 'https://'])
                ? $notice['image']
                : Storage::url($notice['image']);
        }

        return Inertia::render('admin/NoticeEdit', [
            'notice' => $notice,
        ]);
    }

    public function update(UpdateNoticeRequest $request)
    {
        // Read current stored notice to preserve or delete existing disk images
        $rawNotice = AppSetting::get('site_notice');
        $existing = is_array($rawNotice) ? $rawNotice : (json_decode($rawNotice ?? '', true) ?: []);

        $data = $request->validated();

        // If user requested image removal, delete the previous image from local/s3 storage
        if ($request->boolean('remove_image')) {
            $rawImage = $existing['image'] ?? null;
            if ($rawImage && ! str($rawImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($rawImage);
            }
            $existing['image'] = null;
        }

        // If new image was uploaded, clean up old disk image first and store new image in 'notices'
        if ($request->hasFile('image')) {
            $rawImage = $existing['image'] ?? null;
            if ($rawImage && ! str($rawImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($rawImage);
            }

            $path = $request->file('image')->store('notices');
            $existing['image'] = $path;
        }

        // Update notice metadata fields in key-value structure
        $existing['title'] = $data['title'] ?? null;
        $existing['message'] = $data['message'] ?? null;
        $existing['show_button'] = (bool) ($data['show_button'] ?? false);
        $existing['button_title'] = $data['button_title'] ?? null;
        $existing['button_link'] = $data['button_link'] ?? null;
        $existing['is_active'] = (bool) ($data['is_active'] ?? false);
        $existing['updated_at'] = now()->toDateTimeString();

        // Persist JSON payload in app_settings table and bust the cached public display notice
        AppSetting::set('site_notice', json_encode($existing));
        Cache::forget('active_notice_for_display');

        return redirect()->route('admin.notice.edit')->with('success', 'Notice updated.');
    }
}

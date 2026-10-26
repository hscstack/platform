<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlatformSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/PlatformSettings', [
            'settings' => [
                'allow_registration' => (bool) AppSetting::get('allow_registration', true),
                'allow_resource_uploads' => (bool) AppSetting::get('allow_resource_uploads', true),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'allow_registration' => ['required', 'boolean'],
            'allow_resource_uploads' => ['required', 'boolean'],
        ]);

        AppSetting::set('allow_registration', $validated['allow_registration'], 'boolean');
        AppSetting::set('allow_resource_uploads', $validated['allow_resource_uploads'], 'boolean');

        return back()->with('success', 'Platform settings updated successfully.');
    }
}

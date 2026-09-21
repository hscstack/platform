<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('Profile', [
            'user' => $request->user()->load('roles'),
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $openTicketsCount = SupportTicket::where('user_id', $user->id)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])
            ->count();

        return Inertia::render('Account/Delete', [
            'user' => $user->load('roles'),
            'openTicketsCount' => $openTicketsCount,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->boolean('clear_image')) {
            if ($user->image_path) {
                Storage::delete($user->image_path);
            }

            $validated['image_path'] = null;
        } elseif ($request->hasFile('file')) {
            $path = $request->file('file')->store('users/profile-images');

            if ($user->image_path) {
                Storage::delete($user->image_path);
            }

            $validated['image_path'] = $path;
        }

        $user->update($validated);

        return redirect()->route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return Inertia::render('Account/Delete', [
            'openTicketsCount' => SupportTicket::where('user_id', $request->user()->id)
                ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])
                ->count(),
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

    /**
     * Show the signed account deletion confirmation page.
     */
    public function showConfirm(Request $request, User $user)
    {
        $hasValidSignature = $request->hasValidSignature();
        $hasValidHash = ! empty($user->email) && hash_equals(sha1($user->email), (string) $request->query('hash'));

        if (! $hasValidSignature || ! $hasValidHash) {
            return Inertia::render('Account/ConfirmDeletion', [
                'isValid' => false,
                'user' => null,
                'confirmUrl' => '',
            ]);
        }

        return Inertia::render('Account/ConfirmDeletion', [
            'isValid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'confirmUrl' => $request->fullUrl(),
        ]);
    }

    /**
     * Process the permanent deletion requested via signed URL.
     */
    public function confirmDelete(Request $request, User $user)
    {
        if (! hash_equals(sha1($user->email), (string) $request->query('hash'))) {
            abort(403, 'Invalid deletion token.');
        }

        // If currently authenticated as this user, log them out and invalidate session
        if (Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $user->delete();

        return redirect('/')
            ->with('success', 'আপনার অ্যাকাউন্টটি সফলভাবে মুছে ফেলা হয়েছে।');
    }
}

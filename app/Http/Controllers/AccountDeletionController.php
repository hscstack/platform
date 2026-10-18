<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountDeletionController extends Controller
{
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

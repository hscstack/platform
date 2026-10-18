<?php

namespace App\Http\Controllers;

use App\Mail\AccountDeletedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AccountDeletionController extends Controller
{
    /**
     * Show the signed account deletion confirmation page.
     */
    public function showConfirm(Request $request, User $user)
    {
        if (! hash_equals(sha1($user->email), (string) $request->query('hash'))) {
            abort(403, 'Invalid deletion token.');
        }

        return Inertia::render('Account/ConfirmDeletion', [
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

        $email = $user->email;
        $name = $user->name;

        if (! empty($email)) {
            Mail::to($email)->queue(new AccountDeletedMail($name));
        }

        $user->delete();

        return redirect('/')
            ->with('success', 'আপনার অ্যাকাউন্টটি সফলভাবে মুছে ফেলা হয়েছে।');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    //
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('admin.index');
        }

        return Inertia::render('auth/Login');
    }

    public function redirectToGoogle(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('admin.index');
        }

        if ($request->filled('redirect')) {
            $redirectUrl = $request->query('redirect');

            if (str_starts_with($redirectUrl, '/') && !str_starts_with($redirectUrl, '//')) {
                session(['url.intended' => url($redirectUrl)]);
            } else {
                $host = parse_url($redirectUrl, PHP_URL_HOST);
                $appHost = parse_url(config('app.url'), PHP_URL_HOST);

                if ($host && $appHost && ($host === $appHost || str_ends_with($host, '.' . $appHost) || $host === 'localhost')) {
                    session(['url.intended' => $redirectUrl]);
                }
            }
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Failed to authenticate with Google. Please try again.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $isNewUser = false;

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Google User',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ]);
            $isNewUser = true;

            Mail::to($user->email)->queue(new WelcomeUserMail($user));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $redirect = redirect()->intended(route('profile.edit'));

        if ($isNewUser) {
            $redirect->with('success', 'Account not found. New account created.');
        }

        return $redirect;
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index');
    }
}

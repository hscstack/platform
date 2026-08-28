<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

            if (str_starts_with($redirectUrl, '/') && ! str_starts_with($redirectUrl, '//')) {
                session(['url.intended' => url($redirectUrl)]);
            } else {
                $host = parse_url($redirectUrl, PHP_URL_HOST);
                $appHost = parse_url(config('app.url'), PHP_URL_HOST);

                if ($host && $appHost && ($host === $appHost || str_ends_with($host, '.'.$appHost) || $host === 'localhost')) {
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

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            Auth::login($user, remember: true);
            $request->session()->regenerate();

            $defaultUrl = $user->username
                ? route('user.profile', $user->username)
                : route('profile.edit');

            return redirect()->intended($defaultUrl);
        }

        $request->session()->put('onboarding_user', [
            'google_id' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? '',
            'avatar' => $googleUser->getAvatar() ?? null,
        ]);

        return redirect()->route('onboarding');
    }

    public function showOnboarding(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('index');
        }

        if (! $request->session()->has('onboarding_user')) {
            return redirect()->route('login')->with('error', 'Please continue with Google to create an account.');
        }

        $onboardingUser = $request->session()->get('onboarding_user');

        return Inertia::render('auth/Onboarding', [
            'user' => $onboardingUser,
        ]);
    }

    public function completeOnboarding(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('index');
        }

        if (! $request->session()->has('onboarding_user')) {
            return redirect()->route('login')->with('error', 'Session expired. Please continue with Google again.');
        }

        $onboardingData = $request->session()->get('onboarding_user');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:users,username',
            ],
            'school' => ['required', 'string', 'max:255'],
        ], [
            'school.required' => 'Please enter your school, college, or institution name.',
            'username.regex' => 'Username can only contain letters, numbers, and underscores.',
            'username.unique' => 'This username is already taken. Please choose another one.',
        ]);

        $user = User::where('google_id', $onboardingData['google_id'])
            ->orWhere('email', $onboardingData['email'])
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $onboardingData['email'],
                'google_id' => $onboardingData['google_id'],
                'institution' => $validated['school'],
                'email_verified_at' => now(),
            ]);

            Mail::to($user->email)->queue(new WelcomeUserMail($user));
        }

        $request->session()->forget('onboarding_user');

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $defaultUrl = $user->username
            ? route('user.profile', $user->username)
            : route('profile.edit');

        return redirect()->intended($defaultUrl)->with('success', 'Account created successfully! Welcome to HSCStack.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding screen.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If user already customized their username and set institution, skip onboarding
        if (! empty($user->username) && $user->username !== "student_{$user->id}" && ! empty($user->institution)) {
            return redirect()->intended(route('user.profile', ['username' => $user->username]));
        }

        // Generate a clean suggested username from name or email
        $suggestedUsername = $this->generateSuggestedUsername($user);

        return Inertia::render('Onboarding', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'suggested_username' => $suggestedUsername,
                'institution' => $user->institution ?? '',
            ],
        ]);
    }

    /**
     * Complete onboarding and store profile details.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'institution' => ['required', 'string', 'max:255'],
        ], [
            'username.regex' => 'The username may only contain letters, numbers, and underscores.',
            'username.unique' => 'This username is already taken. Please choose another one.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => Str::lower($validated['username']),
            'institution' => $validated['institution'],
        ]);

        return redirect()->intended(route('user.profile', ['username' => $user->username]))
            ->with('success', 'Welcome to HSCStack! Your profile is ready.');
    }

    /**
     * Real-time username availability checker API.
     */
    public function checkUsername(Request $request)
    {
        $username = trim((string) $request->query('username', ''));

        if (empty($username) || strlen($username) < 3 || strlen($username) > 30 || ! preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => 'Username must be 3-30 characters (letters, numbers, underscores).',
            ]);
        }

        $userId = $request->user()?->id;
        $isTaken = User::where('username', Str::lower($username))
            ->when($userId, fn ($query) => $query->where('id', '!=', $userId))
            ->exists();

        return response()->json([
            'valid' => true,
            'available' => ! $isTaken,
            'message' => $isTaken ? 'This username is already taken.' : 'Username is available!',
        ]);
    }

    /**
     * Helper to generate a unique suggested username.
     */
    private function generateSuggestedUsername(User $user): string
    {
        $base = Str::slug($user->name, '_');
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);

        if (empty($base) || strlen($base) < 3) {
            $base = Str::before($user->email, '@');
            $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        }

        $base = Str::lower(substr($base, 0, 20));

        if (strlen($base) < 3) {
            $base = 'student';
        }

        $suggested = $base;
        $counter = 1;

        while (User::where('username', $suggested)->where('id', '!=', $user->id)->exists()) {
            $suggested = "{$base}_{$counter}";
            $counter++;
        }

        return $suggested;
    }
}

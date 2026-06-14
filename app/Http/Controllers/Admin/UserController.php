<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::with('roles')->get(),
        ]);
    }
    public function create()
    {
        return Inertia::render('admin/users/CreateOrEdit');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:6'],
            'role'        => ['required', 'string'],

            'image'       => ['nullable', 'string', 'max:255'],
            'about'       => ['nullable', 'string'],
            'institution' => ['nullable', 'string', 'max:255'],
            'facebook'    => ['nullable', 'string', 'max:255'],
            'github'      => ['nullable', 'string', 'max:255'],
        ]);

        $user = new User();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->email_verified_at = now();

        $user->image = $validated['image'] ?? null;
        $user->about = $validated['about'] ?? null;
        $user->institution = $validated['institution'] ?? null;
        $user->facebook = $validated['facebook'] ?? null;
        $user->github = $validated['github'] ?? null;

        $user->save();

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/CreateOrEdit', [
            'user' => $user->load('roles'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'email'       => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'password'    => ['sometimes', 'nullable', 'string', 'min:6'],
            'role'        => ['sometimes', 'required', 'string'],

            'image'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'about'       => ['sometimes', 'nullable', 'string'],
            'institution' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'github'      => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if (array_key_exists('image', $validated)) {
            $user->image = $validated['image'];
        }

        if (array_key_exists('about', $validated)) {
            $user->about = $validated['about'];
        }

        if (array_key_exists('institution', $validated)) {
            $user->institution = $validated['institution'];
        }

        if (array_key_exists('facebook', $validated)) {
            $user->facebook = $validated['facebook'];
        }

        if (array_key_exists('github', $validated)) {
            $user->github = $validated['github'];
        }

        $user->save();

        if (array_key_exists('role', $validated)) {
            $user->syncRoles([$validated['role']]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}

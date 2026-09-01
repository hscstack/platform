<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = (string) $request->role;
            if ($role === 'staff') {
                $query->has('roles');
            } elseif ($role === 'student') {
                $query->doesntHave('roles');
            } elseif (in_array($role, ['admin', 'editor', 'manager'])) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }
        }

        $users = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => [
                'q' => $request->q ?? '',
                'role' => $request->role ?? 'all',
            ],
            'counts' => [
                'all' => User::count(),
                'staff' => User::has('roles')->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/CreateOrEdit', [
            'permissions' => Permission::select('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {

        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('users/profile-images');
            $validated['image_path'] = $path;
        }

        $user = User::create($validated);
        $role = $validated['role'] ?? null;

        if (! empty($role)) {
            $user->syncRoles([$role]);

            if ($role === 'editor') {
                $user->syncPermissions(
                    $validated['permissions'] ?? []
                );
            } else {
                $user->syncPermissions([]);
            }
        } else {
            $user->syncRoles([]);
            $user->syncPermissions([]);
        }

        $user->notify(new WelcomeNotification);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/CreateOrEdit', [
            'user' => $user->load(['roles', 'permissions']),
            'permissions' => Permission::select('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        if ($user->email === 'check@example.com') {
            abort(403, 'This demo user cannot be modified.');
        }

        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('users/profile-images');

            if ($user->image_path) {
                Storage::delete($user->image_path);
            }

            $validated['image_path'] = $path;
        }

        $user->update($validated);

        if (array_key_exists('role', $validated)) {
            $role = $validated['role'];

            if (! empty($role)) {
                $user->syncRoles([$role]);

                if ($role === 'editor') {
                    $user->syncPermissions(
                        $validated['permissions'] ?? []
                    );
                } else {
                    $user->syncPermissions([]);
                }
            } else {
                $user->syncRoles([]);
                $user->syncPermissions([]);
            }
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Log in as the specified user.
     */
    public function loginAs(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('index')->with('success', "Logged in as {$user->name}.");
    }
}

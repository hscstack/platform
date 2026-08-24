<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Permission;

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
        return Inertia::render('admin/users/CreateOrEdit', [
            'permissions' => Permission::select('name')->get()
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
        $role = $validated['role'];
        $user->syncRoles([$role]);

        if ($role === 'editor') {
            $user->syncPermissions(
                $validated['permissions'] ?? []
            );
        } else {
            $user->syncPermissions([]);
        }


        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/CreateOrEdit', [
            'user' => $user->load(['roles', 'permissions']),
            'permissions' => Permission::select('name')->get(),
            'shouldHideOptions' => false,
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

        if (isset($validated['role'])) {
            $role = $validated['role'];

            $user->syncRoles([$role]);

            if ($role === 'editor') {
                $user->syncPermissions(
                    $validated['permissions'] ?? []
                );
            } else {
                $user->syncPermissions([]);
            }
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

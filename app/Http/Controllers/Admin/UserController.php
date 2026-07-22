<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
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

        Cache::forget('about_us_info');

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/CreateOrEdit', [
            'user' => $user->load(['roles', 'permissions']),
            'permissions' => Permission::select('name')->get()
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
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

        Cache::forget('about_us_info');
        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    function destroy(User $user)
    {
        $user->delete();
        Cache::forget('about_us_info');
        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}

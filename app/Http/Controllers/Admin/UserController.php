<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create($validated);
        $user->assignRole($validated['role']);
        Cache::forget('about_us_info');

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/CreateOrEdit', [
            'user' => $user->load('roles'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        $user->update($validated);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
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

<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('guests cannot access the admin dashboard', function () {
    $response = $this->get('/admin/');

    $response->assertRedirect(route('login'));
});

test('authorized users can access the admin dashboard', function () {
    $user = adminUserWithPermissions(['view admin']);

    $response = $this->actingAs($user)->get('/admin/');

    $response->assertStatus(200);
});

test('authenticated users without view admin permission cannot access admin dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/');

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});

test('users without create subjects permission cannot create subjects', function () {
    $admin = adminUserWithPermissions(['view admin']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Blocked Subject',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});

test('authorized users can access the admin dashboard through a role with view admin permission', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::firstOrCreate(['name' => 'view admin', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/admin/');

    $response->assertStatus(200);
});

test('authenticated user can view their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');

    $response->assertStatus(200);
});

test('authenticated user can update their profile without changing email', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'original@example.com',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'New Name',
        'email' => 'changed@example.com',
        'title' => 'Engineer',
        'institution' => 'Tech Corp',
        'facebook' => 'https://facebook.com/new',
        'github' => 'https://github.com/new',
        'instagram' => 'https://instagram.com/new',
        'about' => 'Hello world bio',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('success', 'Profile updated successfully.');
    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->title)->toBe('Engineer')
        ->and($user->fresh()->email)->toBe('original@example.com');
});

test('non-manage-users cannot access admin user edit route', function () {
    $admin = adminUserWithPermissions(['view admin']);
    $targetUser = User::factory()->create();

    $response = $this->actingAs($admin)->get("/admin/users/edit/{$targetUser->id}");

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});

test('users with manage users permission can access admin user edit route', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage users']);
    $targetUser = User::factory()->create();

    $response = $this->actingAs($admin)->get("/admin/users/edit/{$targetUser->id}");

    $response->assertStatus(200);
});

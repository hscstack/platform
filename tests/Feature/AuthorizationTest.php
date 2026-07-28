<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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

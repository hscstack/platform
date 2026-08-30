<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('view users', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $editorRole = Role::findOrCreate('editor', 'web');
    $managerRole = Role::findOrCreate('manager', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('admin can view user list with all users by default', function () {
    $admin = adminUserWithPermissions(['view admin', 'view users']);
    $admin->assignRole('admin');
    $student = User::factory()->create(['name' => 'Student Alice']);
    $editor = User::factory()->create(['name' => 'Staff Bob']);
    $editor->assignRole('editor');

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/Index')
        ->has('users.data', 3)
        ->where('filters.role', 'all')
        ->where('counts.staff', 2)
    );
});

test('admin can filter user list to show only staff members', function () {
    $admin = adminUserWithPermissions(['view admin', 'view users']);
    $admin->assignRole('admin');
    $student1 = User::factory()->create(['name' => 'Student Alice']);
    $student2 = User::factory()->create(['name' => 'Student Charlie']);
    $editor = User::factory()->create(['name' => 'Editor Dave']);
    $editor->assignRole('editor');
    $manager = User::factory()->create(['name' => 'Manager Eve']);
    $manager->assignRole('manager');

    $response = $this->actingAs($admin)->get('/admin/users?role=staff');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/Index')
        ->has('users.data', 3) // admin + editor + manager
        ->where('filters.role', 'staff')
        ->where('counts.staff', 3)
        ->where('counts.all', 5)
    );
});

test('admin can filter staff members with search query', function () {
    $admin = adminUserWithPermissions(['view admin', 'view users']);
    $editor1 = User::factory()->create(['name' => 'Editor Dave', 'email' => 'dave@example.com']);
    $editor1->assignRole('editor');
    $editor2 = User::factory()->create(['name' => 'Editor Frank', 'email' => 'frank@example.com']);
    $editor2->assignRole('editor');
    $student = User::factory()->create(['name' => 'Dave Student', 'email' => 'davestudent@example.com']);

    $response = $this->actingAs($admin)->get('/admin/users?role=staff&q=Dave');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/Index')
        ->has('users.data', 1)
        ->where('users.data.0.name', 'Editor Dave')
        ->where('filters.role', 'staff')
        ->where('filters.q', 'Dave')
    );
});

<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('manage users', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('admin with manage users permission can log in as another user', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['name' => 'Student User']);

    $response = $this->actingAs($admin)
        ->post(route('admin.users.login-as', $targetUser));

    $response->assertRedirect(route('index'));
    $response->assertSessionHas('success', 'Logged in as Student User.');


    $this->assertAuthenticatedAs($targetUser);
});

test('regular user cannot log in as another user', function () {
    $regularUser = User::factory()->create(['name' => 'Regular User']);
    $targetUser = User::factory()->create(['name' => 'Another User']);

    $response = $this->actingAs($regularUser)
        ->post(route('admin.users.login-as', $targetUser));

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');

    $this->assertAuthenticatedAs($regularUser);
});

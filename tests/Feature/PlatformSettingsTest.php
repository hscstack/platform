<?php

use App\Models\AppSetting;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('manage settings', 'web');
});

test('admin with manage settings permission can view platform settings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view admin', 'manage settings']);

    $this->actingAs($user)
        ->get('/admin/settings/platform')
        ->assertStatus(200);
});

test('admin without manage settings permission cannot access platform settings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view admin');

    $this->actingAs($user)
        ->get('/admin/settings/platform')
        ->assertStatus(302)
        ->assertSessionHas('error', 'You do not have permission to perform this action.');
});

test('admin can update platform settings toggles', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view admin', 'manage settings']);

    $this->actingAs($user)
        ->post('/admin/settings/platform', [
            'allow_registration' => false,
            'allow_resource_uploads' => false,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(AppSetting::get('allow_registration'))->toBeFalse()
        ->and(AppSetting::get('allow_resource_uploads'))->toBeFalse();
});

<?php

use App\Models\User;

test('authenticated user can update their username in profile', function () {
    $user = User::factory()->create([
        'username' => 'student_1',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => $user->name,
        'username' => 'custom_tajim',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->username)->toBe('custom_tajim');
});

test('user cannot update username to one already taken by someone else', function () {
    User::factory()->create([
        'username' => 'existing_user',
    ]);

    $user = User::factory()->create([
        'username' => 'my_user',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => $user->name,
        'username' => 'existing_user',
    ]);

    $response->assertSessionHasErrors(['username']);
    expect($user->fresh()->username)->toBe('my_user');
});

test('user can submit profile without changing their existing username', function () {
    $user = User::factory()->create([
        'username' => 'my_handle',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'Updated Name',
        'username' => 'my_handle',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->name)->toBe('Updated Name');
    expect($user->fresh()->username)->toBe('my_handle');
});

test('username must follow alphanumeric and underscore format', function () {
    $user = User::factory()->create([
        'username' => 'valid_username',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => $user->name,
        'username' => 'invalid user!@#',
    ]);

    $response->assertSessionHasErrors(['username']);
});

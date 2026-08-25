<?php

use App\Models\User;

test('users can opt out of emails from profile', function () {
    $user = User::factory()->create([
        'receive_emails' => true,
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name,
        'receive_emails' => false,
    ]);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('success');

    $this->assertFalse($user->fresh()->receive_emails);
});

test('users can re-enable emails from profile', function () {
    $user = User::factory()->create([
        'receive_emails' => false,
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name,
        'receive_emails' => true,
    ]);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('success');

    $this->assertTrue($user->fresh()->receive_emails);
});

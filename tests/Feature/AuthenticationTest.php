<?php

use App\Models\User;

test('login page is accessible', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.index'));
    $this->assertAuthenticatedAs($user);
});

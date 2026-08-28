<?php

use App\Models\User;

test('guest is redirected to login when accessing onboarding', function () {
    $response = $this->get(route('onboarding.index'));

    $response->assertRedirect(route('login'));
});

test('new user can view onboarding page with suggested username', function () {
    $user = User::factory()->create([
        'name' => 'Tajim Ahmed',
        'username' => 'student_999',
        'institution' => null,
    ]);

    $response = $this->actingAs($user)->get(route('onboarding.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Onboarding')
        ->has('user.suggested_username')
        ->where('user.name', 'Tajim Ahmed')
    );
});

test('already onboarded user is redirected to profile when accessing onboarding', function () {
    $user = User::factory()->create([
        'username' => 'tajim_pro',
        'institution' => 'Notre Dame College',
    ]);

    $response = $this->actingAs($user)->get(route('onboarding.index'));

    $response->assertRedirect(route('user.profile', ['username' => 'tajim_pro']));
});

test('user can complete onboarding and is redirected to profile', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'username' => 'student_123',
        'institution' => null,
    ]);

    $response = $this->actingAs($user)->post(route('onboarding.store'), [
        'name' => 'Tajim Ahmed',
        'username' => 'tajim_ahmed',
        'institution' => 'Notre Dame College',
    ]);

    $response->assertRedirect(route('user.profile', ['username' => 'tajim_ahmed']));

    $user->refresh();
    expect($user->name)->toBe('Tajim Ahmed');
    expect($user->username)->toBe('tajim_ahmed');
    expect($user->institution)->toBe('Notre Dame College');
});

test('onboarding validates unique username', function () {
    User::factory()->create([
        'username' => 'existing_handle',
    ]);

    $user = User::factory()->create([
        'username' => 'student_456',
        'institution' => null,
    ]);

    $response = $this->actingAs($user)->post(route('onboarding.store'), [
        'name' => 'Tajim Ahmed',
        'username' => 'existing_handle',
        'institution' => 'Notre Dame College',
    ]);

    $response->assertSessionHasErrors(['username']);
});

test('onboarding validates required fields', function () {
    $user = User::factory()->create([
        'username' => 'student_789',
        'institution' => null,
    ]);

    $response = $this->actingAs($user)->post(route('onboarding.store'), [
        'name' => '',
        'username' => '',
        'institution' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'username', 'institution']);
});

test('check username endpoint returns availability status', function () {
    User::factory()->create([
        'username' => 'taken_user',
    ]);

    $user = User::factory()->create();

    // Check taken username
    $response = $this->actingAs($user)->getJson('/api/check-username?username=taken_user');
    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'available' => false,
        ]);

    // Check available username
    $response = $this->actingAs($user)->getJson('/api/check-username?username=fresh_user_123');
    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'available' => true,
        ]);

    // Check invalid format
    $response = $this->actingAs($user)->getJson('/api/check-username?username=ab');
    $response->assertOk()
        ->assertJson([
            'valid' => false,
            'available' => false,
        ]);
});

test('onboarding respects intended redirect url', function () {
    $user = User::factory()->create([
        'username' => 'student_555',
        'institution' => null,
    ]);

    // Set intended URL in session
    session()->put('url.intended', '/chat');

    $response = $this->actingAs($user)->post(route('onboarding.store'), [
        'name' => 'Tajim Ahmed',
        'username' => 'tajim_chat',
        'institution' => 'Notre Dame College',
    ]);

    $response->assertRedirect('/chat');
});

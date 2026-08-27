<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

test('login page is accessible', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('redirects to google for authentication', function () {
    $response = $this->get(route('auth.google'));

    $response->assertRedirect();
    $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
});

test('google auth creates new user account if not exists and flashes notice', function () {
    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-12345');
    $abstractUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Google User');
    $abstractUser->shouldReceive('getNickname')->andReturn('googleuser');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get(route('auth.google.callback'));

    $user = User::where('email', 'newuser@example.com')->first();
    $response->assertRedirect(route('user.profile', $user->username));
    $response->assertSessionHas('success', 'Account not found. New account created.');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'google_id' => 'google-id-12345',
        'name' => 'Google User',
    ]);
});

test('redirects to custom redirect url after authentication if provided', function () {
    $this->get('/auth/google?redirect=/ai');

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-custom');
    $abstractUser->shouldReceive('getEmail')->andReturn('redirect-test@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Redirect User');
    $abstractUser->shouldReceive('getNickname')->andReturn('redirectuser');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(url('/ai'));
});

test('redirects to trusted subdomain after authentication if provided', function () {
    config(['app.url' => 'https://hscstack.site']);
    $subdomainUrl = 'https://ssc2026.hscstack.site';
    $this->get('/auth/google?redirect='.urlencode($subdomainUrl));

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-subdomain');
    $abstractUser->shouldReceive('getEmail')->andReturn('subdomain@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Subdomain User');
    $abstractUser->shouldReceive('getNickname')->andReturn('subdomainuser');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect($subdomainUrl);
});

test('existing user logs in with google directly and links google id', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-99999');
    $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Existing User');
    $abstractUser->shouldReceive('getNickname')->andReturn('existing');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    $this->assertEquals('google-id-99999', $user->fresh()->google_id);
});

test('failed google auth redirects to login with error', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new Exception('Invalid state'));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Failed to authenticate with Google. Please try again.');
});

test('google auth redirects to intended url if set', function () {
    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-intended');
    $abstractUser->shouldReceive('getEmail')->andReturn('intended@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Intended User');
    $abstractUser->shouldReceive('getNickname')->andReturn('intended');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    // Attempt to access auth-protected route while logged out
    $this->get('/profile');

    // Authenticate with Google
    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('profile.edit'));
});

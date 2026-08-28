<?php

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
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

test('google auth transfers new user to onboarding without creating account immediately', function () {
    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-12345');
    $abstractUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Google User');
    $abstractUser->shouldReceive('getNickname')->andReturn('googleuser');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('onboarding'));
    $this->assertGuest();
    $this->assertDatabaseMissing('users', [
        'email' => 'newuser@example.com',
    ]);
    $response->assertSessionHas('onboarding_user', function ($data) {
        return $data['email'] === 'newuser@example.com'
            && $data['google_id'] === 'google-id-12345'
            && $data['name'] === 'Google User'
            && $data['avatar'] === 'https://example.com/avatar.jpg';
    });
});

test('onboarding page is accessible with onboarding session', function () {
    $response = $this->withSession([
        'onboarding_user' => [
            'google_id' => 'google-id-12345',
            'email' => 'newuser@example.com',
            'name' => 'Google User',
            'avatar' => null,
        ],
    ])->get(route('onboarding'));

    $response->assertStatus(200);
});

test('onboarding page redirects to login without onboarding session', function () {
    $response = $this->get(route('onboarding'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Please continue with Google to create an account.');
});

test('completing onboarding creates user, queues welcome mail, and logs in', function () {
    Mail::fake();

    $response = $this->withSession([
        'onboarding_user' => [
            'google_id' => 'google-id-12345',
            'email' => 'newuser@example.com',
            'name' => 'Google User',
            'avatar' => null,
        ],
    ])->post(route('onboarding.complete'), [
        'name' => 'Custom Name',
        'username' => 'custom_handle',
        'school' => 'Notre Dame College',
    ]);

    $user = User::where('email', 'newuser@example.com')->first();
    $this->assertNotNull($user);
    $this->assertEquals('Custom Name', $user->name);
    $this->assertEquals('custom_handle', $user->username);
    $this->assertEquals('Notre Dame College', $user->institution);
    $this->assertEquals('google-id-12345', $user->google_id);
    $this->assertNotNull($user->email_verified_at);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('user.profile', 'custom_handle'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('onboarding_user');

    Mail::assertQueued(WelcomeUserMail::class, function ($mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('completing onboarding validates username uniqueness and format', function () {
    User::factory()->create([
        'username' => 'taken_handle',
    ]);

    $response = $this->withSession([
        'onboarding_user' => [
            'google_id' => 'google-id-12345',
            'email' => 'newuser@example.com',
            'name' => 'Google User',
            'avatar' => null,
        ],
    ])->post(route('onboarding.complete'), [
        'name' => 'Custom Name',
        'username' => 'taken_handle',
        'school' => 'Dhaka College',
    ]);

    $response->assertSessionHasErrors(['username']);
    $this->assertGuest();
});

test('completing onboarding requires school field', function () {
    $response = $this->withSession([
        'onboarding_user' => [
            'google_id' => 'google-id-12345',
            'email' => 'newuser@example.com',
            'name' => 'Google User',
            'avatar' => null,
        ],
    ])->post(route('onboarding.complete'), [
        'name' => 'Custom Name',
        'username' => 'valid_handle',
        'school' => '',
    ]);

    $response->assertSessionHasErrors(['school']);
    $this->assertGuest();
});

test('redirects to custom redirect url after onboarding for new user', function () {
    $this->get('/auth/google?redirect=/ai');

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-new-redirect');
    $abstractUser->shouldReceive('getEmail')->andReturn('new-redirect@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('New Redirect User');
    $abstractUser->shouldReceive('getNickname')->andReturn('newredirect');
    $abstractUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $callbackResponse = $this->get(route('auth.google.callback'));
    $callbackResponse->assertRedirect(route('onboarding'));

    // Complete onboarding with intended URL in session
    $onboardResponse = $this->post(route('onboarding.complete'), [
        'name' => 'New Redirect User',
        'username' => 'new_redirect_user',
        'school' => 'Dhaka College',
    ]);

    $onboardResponse->assertRedirect(url('/ai'));
});

test('redirects to custom redirect url after authentication for existing user', function () {
    $user = User::factory()->create([
        'email' => 'redirect-test@example.com',
        'google_id' => 'google-id-custom',
    ]);

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

test('redirects to trusted subdomain after authentication for existing user', function () {
    config(['app.url' => 'https://hscstack.site']);
    $subdomainUrl = 'https://ssc2026.hscstack.site';

    $user = User::factory()->create([
        'email' => 'subdomain@example.com',
        'google_id' => 'google-id-subdomain',
    ]);

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

test('google auth redirects to intended url if set for existing user', function () {
    $user = User::factory()->create([
        'email' => 'intended@example.com',
        'google_id' => 'google-id-intended',
    ]);

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

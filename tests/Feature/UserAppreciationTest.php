<?php

use App\Models\User;
use App\Models\UserAppreciation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected when attempting to appreciate a user', function () {
    $targetUser = User::factory()->create([
        'username' => 'rahim',
    ]);

    $this->post("/u/{$targetUser->id}/appreciate")
        ->assertRedirect('/login');

    expect(UserAppreciation::count())->toBe(0);
});

test('authenticated user can appreciate another user profile', function () {
    $userA = User::factory()->create(['username' => 'alice']);
    $userB = User::factory()->create(['username' => 'bob']);

    $this->actingAs($userA)
        ->post("/u/{$userB->id}/appreciate")
        ->assertRedirect();

    expect(UserAppreciation::where('user_id', $userB->id)->where('appreciator_id', $userA->id)->exists())->toBeTrue();
    expect($userB->appreciationsReceived()->count())->toBe(1);
    expect($userA->appreciationsGiven()->count())->toBe(1);
});

test('clicking appreciate again toggles it off', function () {
    $userA = User::factory()->create(['username' => 'alice']);
    $userB = User::factory()->create(['username' => 'bob']);

    UserAppreciation::create([
        'user_id' => $userB->id,
        'appreciator_id' => $userA->id,
    ]);

    $this->actingAs($userA)
        ->post("/u/{$userB->id}/appreciate")
        ->assertRedirect();

    expect(UserAppreciation::where('user_id', $userB->id)->where('appreciator_id', $userA->id)->exists())->toBeFalse();
    expect($userB->appreciationsReceived()->count())->toBe(0);
});

test('users cannot appreciate their own profile', function () {
    $user = User::factory()->create(['username' => 'alice']);

    $this->actingAs($user)
        ->post("/u/{$user->id}/appreciate")
        ->assertRedirect();

    expect(UserAppreciation::count())->toBe(0);
});

test('user profile displays accurate appreciation counts and status', function () {
    $profileUser = User::factory()->create(['username' => 'recipient']);
    $fan1 = User::factory()->create(['username' => 'fan1']);
    $fan2 = User::factory()->create(['username' => 'fan2']);
    $idol = User::factory()->create(['username' => 'idol']);

    // Fan 1 & 2 appreciate profileUser
    UserAppreciation::create(['user_id' => $profileUser->id, 'appreciator_id' => $fan1->id]);
    UserAppreciation::create(['user_id' => $profileUser->id, 'appreciator_id' => $fan2->id]);

    // profileUser appreciates idol
    UserAppreciation::create(['user_id' => $idol->id, 'appreciator_id' => $profileUser->id]);

    // Check as fan1 (should see isAppreciated = true)
    $this->actingAs($fan1)
        ->get("/u/{$profileUser->username}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Show')
            ->where('appreciationsCount', 2)
            ->where('appreciatingCount', 1)
            ->where('isAppreciated', true)
            ->has('appreciators', 2)
            ->has('appreciating', 1)
            ->has('recentActivities.appreciations', 1)
        );

    // Check as unauthenticated guest (isAppreciated = false)
    auth()->logout();
    $this->get("/u/{$profileUser->username}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Show')
            ->where('appreciationsCount', 2)
            ->where('appreciatingCount', 1)
            ->where('isAppreciated', false)
        );
});

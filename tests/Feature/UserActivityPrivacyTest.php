<?php

use App\Models\ForumPost;
use App\Models\User;

test('public profile shows forum activity and stats to guests', function () {
    $user = User::factory()->create([
        'username' => 'publicuser',
        'activity_privacy' => 'public',
    ]);

    ForumPost::factory()->create([
        'user_id' => $user->id,
        'title' => 'Public User Question',
    ]);

    $response = $this->get(route('user.profile', ['username' => 'publicuser']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('User/Show')
            ->where('isLocked', false)
            ->where('stats.questionsCount', 1)
            ->has('forumPosts', 1)
        );
});

test('private profile hides activity and returns isLocked=true for visitors', function () {
    $user = User::factory()->create([
        'username' => 'privateuser',
        'activity_privacy' => 'private',
    ]);

    ForumPost::factory()->create([
        'user_id' => $user->id,
        'title' => 'Secret Question',
    ]);

    $visitor = User::factory()->create();

    $response = $this->actingAs($visitor)->get(route('user.profile', ['username' => 'privateuser']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('User/Show')
            ->where('isLocked', true)
            ->where('lockReason', 'private')
            ->where('stats.questionsCount', 0)
            ->missing('forumPosts')
        );
});

test('private profile owner can view their own activity', function () {
    $user = User::factory()->create([
        'username' => 'owneruser',
        'activity_privacy' => 'private',
    ]);

    ForumPost::factory()->create([
        'user_id' => $user->id,
        'title' => 'Owner Question',
    ]);

    $response = $this->actingAs($user)->get(route('user.profile', ['username' => 'owneruser']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('User/Show')
            ->where('isLocked', false)
            ->where('stats.questionsCount', 1)
            ->has('forumPosts', 1)
        );
});

test('appreciators_only profile is locked for non-appreciators', function () {
    $user = User::factory()->create([
        'username' => 'creatoruser',
        'activity_privacy' => 'appreciators_only',
    ]);

    ForumPost::factory()->create([
        'user_id' => $user->id,
        'title' => 'Exclusive Question',
    ]);

    $visitor = User::factory()->create();

    $response = $this->actingAs($visitor)->get(route('user.profile', ['username' => 'creatoruser']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('User/Show')
            ->where('isLocked', true)
            ->where('lockReason', 'appreciators_only')
            ->where('stats.questionsCount', 0)
            ->missing('forumPosts')
        );
});

test('appreciators_only profile is unlocked for users who appreciate them', function () {
    $user = User::factory()->create([
        'username' => 'creatoruser',
        'activity_privacy' => 'appreciators_only',
    ]);

    ForumPost::factory()->create([
        'user_id' => $user->id,
        'title' => 'Exclusive Question',
    ]);

    $fan = User::factory()->create();
    $user->appreciators()->attach($fan->id);

    $response = $this->actingAs($fan)->get(route('user.profile', ['username' => 'creatoruser']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('User/Show')
            ->where('isLocked', false)
            ->where('stats.questionsCount', 1)
            ->has('forumPosts', 1)
        );
});

test('user can update activity_privacy in profile settings', function () {
    $user = User::factory()->create([
        'activity_privacy' => 'public',
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Updated Name',
        'username' => $user->username,
        'activity_privacy' => 'appreciators_only',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->activity_privacy)->toBe('appreciators_only');
});

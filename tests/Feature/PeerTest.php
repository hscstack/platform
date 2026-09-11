<?php

use App\Models\User;

test('peers index page loads successfully', function () {
    User::factory()->count(5)->create();

    $response = $this->get(route('peers.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Peers/Index')
            ->has('peers.data')
            ->has('filters')
        );
});

test('peers can be filtered by search keyword', function () {
    User::factory()->create([
        'name' => 'Alice Rahman',
        'username' => 'alicerahman',
        'institution' => 'Notre Dame College',
    ]);

    User::factory()->create([
        'name' => 'Bob Ahmed',
        'username' => 'bobahmed',
        'institution' => 'Dhaka College',
    ]);

    $response = $this->get(route('peers.index', ['search' => 'Notre Dame']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Peers/Index')
            ->where('peers.total', 1)
            ->where('peers.data.0.name', 'Alice Rahman')
        );
});

test('peers can be sorted by appreciation count', function () {
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);
    $admirer = User::factory()->create();

    $user2->appreciators()->attach($admirer->id);

    $response = $this->get(route('peers.index', ['sort' => 'appreciated']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Peers/Index')
            ->where('peers.data.0.name', 'User Two')
        );
});

test('peers in You May Know prioritizes matching institution keywords', function () {
    $viewer = User::factory()->create([
        'name' => 'Current Viewer',
        'institution' => 'Rangpur Zilla School, Rangpur',
    ]);

    $schoolmate = User::factory()->create([
        'name' => 'School Mate',
        'institution' => 'Rangpur Zilla School',
    ]);

    $otherStudent = User::factory()->create([
        'name' => 'Other Student',
        'institution' => 'Chittagong College',
    ]);

    $response = $this->actingAs($viewer)->get(route('peers.index', ['sort' => 'relevant']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Peers/Index')
            ->where('peers.data.0.name', 'School Mate')
        );
});

test('peers in You May Know matches Bengali institution keywords', function () {
    $viewer = User::factory()->create([
        'name' => 'Current Viewer',
        'institution' => 'নটর ডেম কলেজ, ঢাকা',
    ]);

    $schoolmate = User::factory()->create([
        'name' => 'Bengali School Mate',
        'institution' => 'নটর ডেম কলেজ',
    ]);

    $otherStudent = User::factory()->create([
        'name' => 'Other Student',
        'institution' => 'রাজশাহী কলেজ',
    ]);

    $response = $this->actingAs($viewer)->get(route('peers.index', ['sort' => 'relevant']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Peers/Index')
            ->where('peers.data.0.name', 'Bengali School Mate')
        );
});

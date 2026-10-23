<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceVote;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ResourceVoteNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-vote-test',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $this->node = Node::create([
        'subject_id' => $this->subject->id,
        'name' => 'Mechanics',
        'slug' => 'mechanics',
    ]);
    $this->resource = Resource::create([
        'node_id' => $this->node->id,
        'user_id' => $this->author->id,
        'resource_type' => 'note',
        'title' => 'Newtonian Mechanics Note',
        'description' => 'Test description',
        'status' => 'approved',
    ]);
});

test('guests are redirected to login when attempting to vote', function () {
    $response = $this->post("/resources/{$this->resource->id}/vote", [
        'value' => 1,
    ]);

    $response->assertRedirect('/login');
    expect(ResourceVote::count())->toBe(0);
});

test('authenticated user can upvote and un-vote a resource', function () {
    Notification::fake();
    $voter = User::factory()->create();

    // 1. Upvote
    $response = $this->actingAs($voter)->postJson("/resources/{$this->resource->id}/vote", [
        'value' => 1,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'vote_score' => 1,
            'upvotes_count' => 1,
            'downvotes_count' => 0,
            'user_vote' => 1,
        ]);

    expect($this->resource->fresh()->vote_score)->toBe(1);
    Notification::assertSentTo($this->author, ResourceVoteNotification::class);

    // 2. Un-vote (clicking upvote again)
    $response2 = $this->actingAs($voter)->postJson("/resources/{$this->resource->id}/vote", [
        'value' => 1,
    ]);

    $response2->assertOk()
        ->assertJson([
            'success' => true,
            'vote_score' => 0,
            'upvotes_count' => 0,
            'downvotes_count' => 0,
            'user_vote' => null,
        ]);

    expect($this->resource->fresh()->vote_score)->toBe(0);
});

test('authenticated user can switch from downvote to upvote', function () {
    $voter = User::factory()->create();

    // 1. Downvote
    $this->actingAs($voter)->postJson("/resources/{$this->resource->id}/vote", [
        'value' => -1,
    ])->assertOk()->assertJson([
        'vote_score' => -1,
        'upvotes_count' => 0,
        'downvotes_count' => 1,
        'user_vote' => -1,
    ]);

    // 2. Switch to Upvote
    $this->actingAs($voter)->postJson("/resources/{$this->resource->id}/vote", [
        'value' => 1,
    ])->assertOk()->assertJson([
        'vote_score' => 1,
        'upvotes_count' => 1,
        'downvotes_count' => 0,
        'user_vote' => 1,
    ]);

    expect($this->resource->fresh()->vote_score)->toBe(1);
});

test('author does not receive notification when upvoting own resource', function () {
    Notification::fake();

    $this->actingAs($this->author)->postJson("/resources/{$this->resource->id}/vote", [
        'value' => 1,
    ])->assertOk();

    Notification::assertNothingSent();
});

<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('moderate resources', 'web');

    $this->subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'icon' => 'atom',
    ]);

    $this->node = Node::create([
        'subject_id' => $this->subject->id,
        'name' => 'Mechanics',
        'slug' => 'mechanics',
    ]);
});

test('authenticated user can upload a note for moderation without create resources permission', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($user)->post('/resources', [
        'node_id' => $this->node->id,
        'resource_type' => 'note',
        'title' => 'Newton Laws of Motion',
        'content' => 'First, second, and third laws explained.',
    ]);

    $response->assertSessionHas('success', 'Resource submitted for moderation.');

    // Resource should NOT exist in resources table yet
    expect(Resource::where('title', 'Newton Laws of Motion')->exists())->toBeFalse();

    // Change request should exist with pending status
    $changeRequest = ResourceChangeRequest::where('user_id', $user->id)
        ->where('action_type', 'create')
        ->first();

    expect($changeRequest)->not->toBeNull()
        ->and($changeRequest->status)->toBe('pending')
        ->and($changeRequest->payload['title'])->toBe('Newton Laws of Motion');
});

test('user younger than 24 hours cannot upload a resource', function () {
    $newbie = User::factory()->create([
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->actingAs($newbie)->post('/resources', [
        'node_id' => $this->node->id,
        'resource_type' => 'note',
        'title' => 'Too early note',
        'content' => 'Some content',
    ]);

    $response->assertSessionHasErrors('pending_limit');
    expect(ResourceChangeRequest::where('user_id', $newbie->id)->exists())->toBeFalse();
});

test('user cannot upload to a frozen folder', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(3),
    ]);

    $this->node->update(['is_frozen' => true]);

    $response = $this->actingAs($user)->post('/resources', [
        'node_id' => $this->node->id,
        'resource_type' => 'note',
        'title' => 'Frozen note',
        'content' => 'Cannot upload here',
    ]);

    $response->assertForbidden();
    expect(ResourceChangeRequest::where('user_id', $user->id)->exists())->toBeFalse();
});

test('moderator can upload directly to a frozen folder', function () {
    $moderator = User::factory()->create([
        'created_at' => now()->subDays(3),
    ]);
    $moderator->givePermissionTo('moderate resources');

    $this->node->update(['is_frozen' => true]);

    $response = $this->actingAs($moderator)->post('/resources', [
        'node_id' => $this->node->id,
        'resource_type' => 'note',
        'title' => 'Moderator override note',
        'content' => 'Admin announcement',
    ]);

    $response->assertSessionHas('success', 'Resource created successfully.');

    expect(Resource::where('title', 'Moderator override note')->exists())->toBeTrue();
});

test('user can view their own submissions on my-submissions page', function () {
    $user = User::factory()->create();

    ResourceChangeRequest::recordCreate($user->id, $this->node->id, [
        'title' => 'User Submission 1',
        'resource_type' => 'note',
    ]);

    $response = $this->actingAs($user)->get('/my-submissions');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Submissions/Index')
            ->has('submissions.data', 1)
            ->where('counts.pending', 1)
            ->where('counts.max_pending', 30)
        );
});

test('user can cancel their pending submission and delete staged file', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('test_note.pdf', 100, 'application/pdf');
    $filePath = $file->store('resources/pdfs');

    $changeRequest = ResourceChangeRequest::create([
        'user_id' => $user->id,
        'node_id' => $this->node->id,
        'action_type' => 'create',
        'status' => 'pending',
        'payload' => [
            'title' => 'Handwritten Notes',
            'resource_type' => 'image',
            'file_path' => $filePath,
        ],
    ]);

    Storage::assertExists($filePath);

    $response = $this->actingAs($user)->delete("/my-submissions/{$changeRequest->id}");

    $response->assertSessionHas('success');
    expect(ResourceChangeRequest::find($changeRequest->id))->toBeNull();
    Storage::assertMissing($filePath);
});

test('user cannot cancel another users submission', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $changeRequest = ResourceChangeRequest::recordCreate($owner->id, $this->node->id, [
        'title' => 'Protected Note',
        'resource_type' => 'note',
    ]);

    $this->actingAs($attacker)
        ->delete("/my-submissions/{$changeRequest->id}")
        ->assertForbidden();

    expect(ResourceChangeRequest::find($changeRequest->id))->not->toBeNull();
});

<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('edit resources', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('unauthorized users cannot bulk rename resources', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
    ]);

    $this->actingAs($user)
        ->post("/admin/nodes/{$node->id}/resources/bulk-rename", [
            'prefix' => 'Class',
            'start_number' => 1,
        ])->assertRedirect();
});

test('admin with edit resources permission can bulk rename resources in sequential order', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subject = Subject::create([
        'name' => 'Higher Math',
        'slug' => 'higher-math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Matrix',
        'slug' => 'matrix',
    ]);

    $res1 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title 1',
        'external_url' => 'https://youtube.com/watch?v=11111111111',
    ]);

    $res2 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title 2',
        'external_url' => 'https://youtube.com/watch?v=22222222222',
    ]);

    $res3 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title 3',
        'external_url' => 'https://youtube.com/watch?v=33333333333',
    ]);

    $this->actingAs($admin)
        ->post("/admin/nodes/{$node->id}/resources/bulk-rename", [
            'prefix' => 'Class - ',
            'start_number' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($res1->fresh()->title)->toBe('Class - 01');
    expect($res2->fresh()->title)->toBe('Class - 02');
    expect($res3->fresh()->title)->toBe('Class - 03');
});

test('bulk rename respects custom starting number', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chemistry',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'flask',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Organic',
        'slug' => 'organic',
    ]);

    $res1 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Intro',
        'external_url' => 'https://youtube.com/watch?v=aaaaa',
    ]);

    $res2 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Part 2',
        'external_url' => 'https://youtube.com/watch?v=bbbbb',
    ]);

    $this->actingAs($admin)
        ->post("/admin/nodes/{$node->id}/resources/bulk-rename", [
            'prefix' => 'Lecture',
            'start_number' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($res1->fresh()->title)->toBe('Lecture - 05');
    expect($res2->fresh()->title)->toBe('Lecture - 06');
});

test('resource author can submit a deletion request for moderation', function () {
    Permission::findOrCreate('delete resources', 'web');

    $author = User::factory()->create();
    $author->givePermissionTo('view admin');

    $subject = Subject::create([
        'name' => 'Math',
        'slug' => 'math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Geometry',
        'slug' => 'geometry',
    ]);

    $resource = Resource::create([
        'user_id' => $author->id,
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'My Video',
        'external_url' => 'https://youtube.com/watch?v=myvideo12345',
    ]);

    $this->actingAs($author)
        ->delete("/admin/resources/{$resource->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    // Live resource remains until approved
    expect(Resource::find($resource->id))->not->toBeNull();

    // Pending deletion request exists in moderation queue
    $request = ResourceChangeRequest::where('resource_id', $resource->id)->first();
    expect($request)->not->toBeNull()
        ->and($request->action_type)->toBe('delete')
        ->and($request->status)->toBe('pending');
});

test('non-author without delete resources permission cannot submit deletion request', function () {
    Permission::findOrCreate('delete resources', 'web');

    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherUser->givePermissionTo('view admin');

    $subject = Subject::create([
        'name' => 'Biology',
        'slug' => 'biology',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'dna',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Genetics',
        'slug' => 'genetics',
    ]);

    $resource = Resource::create([
        'user_id' => $author->id,
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Cell Division',
        'external_url' => 'https://youtube.com/watch?v=cell12345678',
    ]);

    $this->actingAs($otherUser)
        ->delete("/admin/resources/{$resource->id}")
        ->assertForbidden();

    expect(ResourceChangeRequest::where('resource_id', $resource->id)->exists())->toBeFalse();
});

test('resource author can submit an update for moderation while live resource is unchanged', function () {
    $author = User::factory()->create();
    $author->givePermissionTo('view admin');

    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chemistry',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'flask',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Acids',
        'slug' => 'acids',
    ]);

    $resource = Resource::create([
        'user_id' => $author->id,
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title',
        'external_url' => 'https://youtube.com/watch?v=acid11111111',
    ]);

    $this->actingAs($author)
        ->post("/admin/resources/{$resource->id}/patch", [
            'node_id' => $node->id,
            'title' => 'New Proposed Title',
            'resource_type' => 'video',
            'external_url' => 'https://youtube.com/watch?v=acid22222222',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    // Live resource is untouched
    expect($resource->fresh()->title)->toBe('Old Title');

    // Moderation queue has the pending update
    $change = ResourceChangeRequest::where('resource_id', $resource->id)->first();
    expect($change)->not->toBeNull()
        ->and($change->action_type)->toBe('update')
        ->and($change->status)->toBe('pending')
        ->and($change->payload['title'])->toBe('New Proposed Title');
});

test('moderator can approve a create request to bring resource live', function () {
    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $subject = Subject::create([
        'name' => 'Math',
        'slug' => 'math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Algebra',
        'slug' => 'algebra',
    ]);

    $changeRequest = ResourceChangeRequest::recordCreate($moderator->id, $node->id, [
        'title' => 'Equations Note',
        'resource_type' => 'note',
        'content' => 'Algebra notes content',
    ]);

    expect(Resource::where('title', 'Equations Note')->exists())->toBeFalse();

    $this->actingAs($moderator)
        ->post('/admin/moderation/resources/approve', [
            'ids' => [$changeRequest->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($changeRequest->fresh()->status)->toBe('approved')
        ->and($changeRequest->fresh()->reviewed_by)->toBe($moderator->id)
        ->and(Resource::where('title', 'Equations Note')->exists())->toBeTrue();
});

test('moderator can bulk approve multiple requests at once', function () {
    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-bulk',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Mechanics',
        'slug' => 'mechanics',
    ]);

    $req1 = ResourceChangeRequest::recordCreate($moderator->id, $node->id, [
        'title' => 'Bulk Note 1',
        'resource_type' => 'note',
        'content' => 'Content 1',
    ]);

    $req2 = ResourceChangeRequest::recordCreate($moderator->id, $node->id, [
        'title' => 'Bulk Note 2',
        'resource_type' => 'note',
        'content' => 'Content 2',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/moderation/resources/approve', [
            'ids' => [$req1->id, $req2->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($req1->fresh()->status)->toBe('approved')
        ->and($req2->fresh()->status)->toBe('approved')
        ->and(Resource::where('title', 'Bulk Note 1')->exists())->toBeTrue()
        ->and(Resource::where('title', 'Bulk Note 2')->exists())->toBeTrue();
});

test('moderator can reject a request with feedback reason', function () {
    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Optics',
        'slug' => 'optics',
    ]);

    $changeRequest = ResourceChangeRequest::recordCreate($moderator->id, $node->id, [
        'title' => 'Bad Video Link',
        'resource_type' => 'video',
        'external_url' => 'https://youtube.com/watch?v=brokenlink11',
    ]);

    $this->actingAs($moderator)
        ->post("/admin/moderation/resources/{$changeRequest->id}/reject", [
            'rejection_reason' => 'The video URL is not valid.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($changeRequest->fresh()->status)->toBe('rejected')
        ->and($changeRequest->fresh()->rejection_reason)->toBe('The video URL is not valid.')
        ->and(Resource::where('title', 'Bad Video Link')->exists())->toBeFalse();
});

test('moderator can bulk reject multiple requests with shared feedback', function () {
    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-bulk-reject',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Thermodynamics',
        'slug' => 'thermodynamics',
    ]);

    $req1 = ResourceChangeRequest::recordCreate($moderator->id, $node->id, [
        'title' => 'Spam 1',
        'resource_type' => 'note',
    ]);

    $req2 = ResourceChangeRequest::recordCreate($moderator->id, $node->id, [
        'title' => 'Spam 2',
        'resource_type' => 'note',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/moderation/resources/reject', [
            'ids' => [$req1->id, $req2->id],
            'rejection_reason' => 'Duplicate spam uploads.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($req1->fresh()->status)->toBe('rejected')
        ->and($req1->fresh()->rejection_reason)->toBe('Duplicate spam uploads.')
        ->and($req2->fresh()->status)->toBe('rejected')
        ->and($req2->fresh()->rejection_reason)->toBe('Duplicate spam uploads.');
});

test('old reviewed change requests are pruned after 30 days', function () {
    $user = User::factory()->create();

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-prune',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Waves',
        'slug' => 'waves',
    ]);

    // Old approved request (> 30 days)
    $oldApproved = ResourceChangeRequest::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
        'action_type' => 'create',
        'status' => 'approved',
        'reviewed_by' => $user->id,
        'reviewed_at' => now()->subDays(31),
        'payload' => ['title' => 'Old Approved'],
    ]);

    // Recent approved request (<= 30 days)
    $recentApproved = ResourceChangeRequest::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
        'action_type' => 'create',
        'status' => 'approved',
        'reviewed_by' => $user->id,
        'reviewed_at' => now()->subDays(10),
        'payload' => ['title' => 'Recent Approved'],
    ]);

    // Pending request (> 30 days old created_at, but status pending)
    $pendingReq = ResourceChangeRequest::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
        'action_type' => 'create',
        'status' => 'pending',
        'payload' => ['title' => 'Pending Req'],
    ]);

    $this->artisan('model:prune', ['--model' => [ResourceChangeRequest::class]]);

    expect(ResourceChangeRequest::where('id', $oldApproved->id)->exists())->toBeFalse()
        ->and(ResourceChangeRequest::where('id', $recentApproved->id)->exists())->toBeTrue()
        ->and(ResourceChangeRequest::where('id', $pendingReq->id)->exists())->toBeTrue();
});

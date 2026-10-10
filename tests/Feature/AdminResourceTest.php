<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ResourceModerationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
    Notification::fake();

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

    Notification::assertSentToTimes($moderator, ResourceModerationNotification::class, 1);
    Notification::assertSentTo(
        $moderator,
        ResourceModerationNotification::class,
        fn ($notif) => $notif->status === 'approved' && $notif->totalCount === 2
    );
});

test('moderator can reject a request with feedback reason', function () {
    Notification::fake();

    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $author = User::factory()->create();

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

    $changeRequest = ResourceChangeRequest::recordCreate($author->id, $node->id, [
        'title' => 'Bad Video Link',
        'resource_type' => 'video',
        'external_url' => 'https://youtube.com/watch?v=brokenlink11',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/moderation/resources/reject', [
            'ids' => [$changeRequest->id],
            'rejection_reason' => 'The video URL is not valid.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($changeRequest->fresh()->status)->toBe('rejected')
        ->and($changeRequest->fresh()->rejection_reason)->toBe('The video URL is not valid.')
        ->and(Resource::where('title', 'Bad Video Link')->exists())->toBeFalse();

    Notification::assertSentTo(
        $author,
        ResourceModerationNotification::class,
        fn ($notif) => $notif->status === 'rejected' && $notif->feedback === 'The video URL is not valid.'
    );
});

test('moderator can bulk reject multiple requests with shared feedback', function () {
    Notification::fake();

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

    Notification::assertSentToTimes($moderator, ResourceModerationNotification::class, 1);
    Notification::assertSentTo(
        $moderator,
        ResourceModerationNotification::class,
        fn ($notif) => $notif->status === 'rejected' && $notif->totalCount === 2 && $notif->feedback === 'Duplicate spam uploads.'
    );
});

test('old reviewed change requests are pruned after 15 days', function () {
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

    // Old approved request (> 15 days)
    $oldApproved = ResourceChangeRequest::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
        'action_type' => 'create',
        'status' => 'approved',
        'reviewed_by' => $user->id,
        'reviewed_at' => now()->subDays(16),
        'payload' => ['title' => 'Old Approved'],
    ]);

    // Recent approved request (<= 15 days)
    $recentApproved = ResourceChangeRequest::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
        'action_type' => 'create',
        'status' => 'approved',
        'reviewed_by' => $user->id,
        'reviewed_at' => now()->subDays(5),
        'payload' => ['title' => 'Recent Approved'],
    ]);

    // Pending request (> 15 days old created_at, but status pending)
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

test('rejecting a change request preserves the staged file until prune', function () {
    Storage::fake();
    Notification::fake();
    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Math',
        'slug' => 'math-keep-staged',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Geometry',
        'slug' => 'geometry',
    ]);

    Storage::put('resources/geometry.png', 'png-content');

    $req = ResourceChangeRequest::recordCreate($author->id, $node->id, [
        'title' => 'Geometry Diagram',
        'resource_type' => 'image',
        'file_path' => 'resources/geometry.png',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/moderation/resources/reject', [
            'ids' => [$req->id],
            'rejection_reason' => 'Blurry image.',
        ])
        ->assertRedirect();

    expect(Storage::exists('resources/geometry.png'))->toBeTrue()
        ->and($req->fresh()->status)->toBe('rejected');

    // Simulate 16 days passing and pruning running
    $req->update(['reviewed_at' => now()->subDays(16)]);
    $this->artisan('model:prune', ['--model' => [ResourceChangeRequest::class]]);

    expect(ResourceChangeRequest::where('id', $req->id)->exists())->toBeFalse()
        ->and(Storage::exists('resources/geometry.png'))->toBeFalse();
});

test('approving an update preserves omitted attributes on the live resource', function () {
    Permission::findOrCreate('moderate resources', 'web');

    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

    $subject = Subject::create([
        'name' => 'Math',
        'slug' => 'math-update-preserve',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Calculus',
        'slug' => 'calculus',
    ]);

    $resource = Resource::create([
        'user_id' => $moderator->id,
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Original Title',
        'content' => 'Existing description that must not be cleared',
        'external_url' => 'https://example.com/original',
    ]);

    $changeRequest = ResourceChangeRequest::recordUpdate($moderator->id, $resource, [
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Updated Title Only',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/moderation/resources/approve', [
            'ids' => [$changeRequest->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $fresh = $resource->fresh();
    expect($fresh->title)->toBe('Updated Title Only')
        ->and($fresh->content)->toBe('Existing description that must not be cleared')
        ->and($fresh->external_url)->toBe('https://example.com/original');
});

test('resource moderation notification formats batch data correctly', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chem-notif',
        'course' => 'hsc',
        'tailwind_format' => 'bg-emerald-500',
        'icon' => 'flask',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Organic',
        'slug' => 'organic',
    ]);
    $req = ResourceChangeRequest::recordCreate($user->id, $node->id, [
        'title' => 'Sample Note',
        'resource_type' => 'note',
    ]);

    $rejectBatch = new ResourceModerationNotification($req, 'rejected', 'Poor image resolution', 15);
    $rejectData = $rejectBatch->toArray($user);

    expect($rejectData['title'])->toBe('15 Resource Requests Rejected')
        ->and($rejectData['message'])->toBe('15 of your resource requests were rejected: Poor image resolution')
        ->and($rejectData['action_type'])->toBe('batch')
        ->and($rejectData['count'])->toBe(15)
        ->and($rejectData['status'])->toBe('rejected');

    $approveBatch = new ResourceModerationNotification($req, 'approved', null, 5);
    $approveData = $approveBatch->toArray($user);

    expect($approveData['title'])->toBe('5 Resource Requests Approved')
        ->and($approveData['message'])->toBe('5 of your resource requests were approved and are now live.')
        ->and($approveData['action_type'])->toBe('batch')
        ->and($approveData['count'])->toBe(5)
        ->and($approveData['status'])->toBe('approved');
});

test('batch node creation respects should_track_top_folders parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subject = Subject::create([
        'name' => 'Biology',
        'slug' => 'biology-trackable',
        'course' => 'hsc',
        'tailwind_format' => 'bg-emerald-500',
        'icon' => 'dna',
    ]);

    $this->actingAs($admin)
        ->post("/admin/subjects/{$subject->id}/nodes/batch", [
            'nodes' => [
                [
                    'name' => 'Chapter 1 Cell',
                    'slug' => 'ch-1-cell',
                    'children' => [
                        ['name' => 'Classes', 'slug' => 'classes'],
                    ],
                ],
            ],
            'should_track_top_folders' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $topNode = Node::where('slug', 'ch-1-cell')->first();
    $subNode = Node::where('slug', 'classes')->where('parent_id', $topNode->id)->first();

    expect($topNode)->not->toBeNull()
        ->and($topNode->is_trackable)->toBeTrue()
        ->and($subNode)->not->toBeNull()
        ->and($subNode->is_trackable)->toBeFalse();
});

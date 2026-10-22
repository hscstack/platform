<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ResourceModerationNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('moderate resources', 'web');
    Permission::findOrCreate('delete resources', 'web');
    Permission::findOrCreate('create nodes', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('unauthorized users cannot view moderation queue or approve/reject resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/moderation/resources')
        ->assertForbidden();

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-test',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
    ]);
    $resource = Resource::create([
        'node_id' => $node->id,
        'user_id' => $user->id,
        'resource_type' => 'note',
        'title' => 'Test Note',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post('/admin/resources/approve', ['ids' => [$resource->id]])
        ->assertForbidden();

    $this->actingAs($user)
        ->post('/admin/resources/reject', ['ids' => [$resource->id]])
        ->assertForbidden();
});

test('moderator can view resource moderation queue with counts', function () {
    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-queue',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Kinematics',
        'slug' => 'kinematics',
    ]);

    Resource::create([
        'node_id' => $node->id,
        'user_id' => $moderator->id,
        'resource_type' => 'note',
        'title' => 'Pending Note',
        'status' => 'pending',
    ]);

    Resource::create([
        'node_id' => $node->id,
        'user_id' => $moderator->id,
        'resource_type' => 'note',
        'title' => 'Approved Note',
        'status' => 'approved',
    ]);

    $this->actingAs($moderator)
        ->get('/admin/moderation/resources?status=pending')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/moderation/Resources')
            ->has('resources.data', 1)
            ->where('counts.pending', 1)
            ->where('counts.approved', 1)
        );
});

test('moderator can batch approve pending resources to bring them live', function () {
    Notification::fake();

    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Higher Math',
        'slug' => 'higher-math-batch',
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
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Matrix Basics 1',
        'status' => 'pending',
    ]);
    $res2 = Resource::create([
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Matrix Basics 2',
        'status' => 'pending',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/resources/approve', [
            'ids' => [$res1->id, $res2->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($res1->fresh()->status)->toBe('approved')
        ->and($res2->fresh()->status)->toBe('approved')
        ->and($res1->fresh()->reviewed_by)->toBe($moderator->id)
        ->and($res2->fresh()->reviewed_at)->not->toBeNull();

    Notification::assertSentTo($author, ResourceModerationNotification::class);
});

test('approving already approved resource skips update without error', function () {
    Notification::fake();

    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Math II',
        'slug' => 'math-ii-skip',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'book',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Algebra',
        'slug' => 'algebra',
    ]);
    $resource = Resource::create([
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Algebra 101',
        'status' => 'approved',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/resources/approve', [
            'ids' => [$resource->id],
        ])
        ->assertRedirect();

    Notification::assertNothingSent();
});

test('moderator can reject a pending resource with feedback note', function () {
    Notification::fake();

    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $author = User::factory()->create();

    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chemistry-reject',
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
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Blurry Note',
        'status' => 'pending',
    ]);

    $this->actingAs($moderator)
        ->post('/admin/resources/reject', [
            'ids' => [$resource->id],
            'admin_note' => 'Please re-upload with clearer images.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $fresh = $resource->fresh();
    expect($fresh->status)->toBe('rejected')
        ->and($fresh->admin_note)->toBe('Please re-upload with clearer images.')
        ->and($fresh->reviewed_by)->toBe($moderator->id)
        ->and($fresh->reviewed_at)->not->toBeNull();

    Notification::assertSentTo($author, ResourceModerationNotification::class);
});

test('regular user cannot delete resources without permission', function () {
    $author = User::factory()->create();

    $subject = Subject::create([
        'name' => 'Math',
        'slug' => 'math-no-del',
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
        'resource_type' => 'note',
        'title' => 'My Note',
    ]);

    $this->actingAs($author)
        ->delete("/admin/resources/{$resource->id}")
        ->assertForbidden();

    expect(Resource::find($resource->id))->not->toBeNull();
});

test('user with delete resources permission can delete a resource', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('delete resources');

    $subject = Subject::create([
        'name' => 'Biology',
        'slug' => 'biology-del',
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
        'user_id' => $user->id,
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Bad Note',
    ]);

    $this->actingAs($user)
        ->delete("/admin/resources/{$resource->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Resource::find($resource->id))->toBeNull();
});

test('rejected resources are pruned after 15 days via model:prune', function () {
    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-prune',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Mechanics',
        'slug' => 'mechanics',
    ]);

    // Resource 1: Rejected 16 days ago (should be pruned)
    $oldRejected = Resource::create([
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Old Rejected',
        'status' => 'rejected',
        'reviewed_at' => now()->subDays(16),
    ]);

    // Resource 2: Rejected 5 days ago (should be kept)
    $recentRejected = Resource::create([
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Recent Rejected',
        'status' => 'rejected',
        'reviewed_at' => now()->subDays(5),
    ]);

    // Resource 3: Approved 20 days ago (should NOT be pruned)
    $approvedResource = Resource::create([
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Approved Note',
        'status' => 'approved',
        'reviewed_at' => now()->subDays(20),
    ]);

    $this->artisan('model:prune', ['--model' => [Resource::class]])
        ->assertSuccessful();

    expect(Resource::find($oldRejected->id))->toBeNull()
        ->and(Resource::find($recentRejected->id))->not->toBeNull()
        ->and(Resource::find($approvedResource->id))->not->toBeNull();
});

test('batch node creation respects should_track_top_folders parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('create nodes');

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

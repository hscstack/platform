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
    Permission::findOrCreate('manage resources', 'web');
    Permission::findOrCreate('manage curriculum', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('unauthorized users cannot view moderation queue or approve/reject resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/moderation/resources')
        ->assertRedirect()
        ->assertSessionHas('error');

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
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($user)
        ->post('/admin/resources/reject', ['ids' => [$resource->id]])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('moderator can view resource moderation queue with counts', function () {
    $moderator = User::factory()->create();
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

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
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

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
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

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
    $moderator->givePermissionTo(['view admin', 'moderate resources']);

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
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Resource::find($resource->id))->not->toBeNull();
});

test('user with manage resources permission can delete a resource', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['view admin', 'manage resources']);

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
    $admin->givePermissionTo('manage curriculum');

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
                ],
            ],
            'should_track_top_folders' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $topNode = Node::where('slug', 'ch-1-cell')->first();

    expect($topNode)->not->toBeNull()
        ->and($topNode->is_trackable)->toBeTrue();
});

test('user with manage resources permission can update any resource and author can update own resource', function () {
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-update-test',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
    ]);

    $author = User::factory()->create();
    $editor = User::factory()->create();
    $editor->givePermissionTo('manage resources');
    $otherUser = User::factory()->create();

    $resource = Resource::create([
        'node_id' => $node->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Initial Title',
        'items' => [
            ['format' => 'image', 'source' => 'resources/notes/p1.webp', 'title' => 'Page 1'],
            ['format' => 'image', 'source' => 'resources/notes/p2.webp', 'title' => 'Page 2'],
        ],
    ]);

    // 1. Other unauthorized user cannot update
    $this->actingAs($otherUser)
        ->patch("/resources/{$resource->id}", [
            'title' => 'Hacked Title',
            'resource_type' => 'note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/p1.webp'],
            ],
        ])
        ->assertForbidden();

    // 2. Author can update own resource (e.g. reorder pages and change title)
    $this->actingAs($author)
        ->patch("/resources/{$resource->id}", [
            'title' => 'Author Updated Title',
            'description' => 'Author new description',
            'resource_type' => 'note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/p2.webp', 'title' => 'Now Page 1'],
                ['format' => 'image', 'source' => 'resources/notes/p1.webp', 'title' => 'Now Page 2'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $resource->refresh();
    expect($resource->title)->toBe('Author Updated Title')
        ->and($resource->description)->toBe('Author new description')
        ->and(count($resource->items))->toBe(2)
        ->and($resource->items[0]['source'])->toBe('resources/notes/p2.webp')
        ->and($resource->thumbnail)->toContain('resources/notes/p2.webp');

    // 3. User with manage resources permission can update any resource
    $this->actingAs($editor)
        ->patch("/resources/{$resource->id}", [
            'title' => 'Editor Fixed Title',
            'resource_type' => 'video',
            'items' => [
                ['format' => 'video', 'source' => 'https://www.youtube.com/watch?v=lnnYO482hhg', 'title' => 'Class 1'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $resource->refresh();
    expect($resource->title)->toBe('Editor Fixed Title')
        ->and($resource->resource_type)->toBe('video')
        ->and($resource->thumbnail)->toBe('https://img.youtube.com/vi/lnnYO482hhg/hqdefault.jpg');
});

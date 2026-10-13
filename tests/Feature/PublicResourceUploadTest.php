<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ResourceModerationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake();
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('edit resources', 'web');
    Permission::findOrCreate('delete resources', 'web');
    Permission::findOrCreate('moderate resources', 'web');
    Role::findOrCreate('admin', 'web');
});

function createTestSubjectAndNode(bool $isFrozen = false): array
{
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
        'is_frozen' => $isFrozen,
    ]);

    return [$subject, $node];
}

test('guest cannot upload resources', function () {
    [, $node] = createTestSubjectAndNode();

    $this->post('/resources', [
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Test Note',
        'content' => 'Note content',
    ])->assertRedirect('/login');
});

test('public authenticated user older than 24h stages resource into change request', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'created_at' => now()->subHours(25),
    ]);

    $this->actingAs($user)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'My Community Note',
            'content' => 'Some useful content',
        ])
        ->assertSessionHas('success', 'Resource submitted for moderation.');

    expect(Resource::count())->toBe(0);
    expect(ResourceChangeRequest::count())->toBe(1);

    $cr = ResourceChangeRequest::first();
    expect($cr->user_id)->toBe($user->id)
        ->and($cr->node_id)->toBe($node->id)
        ->and($cr->action_type)->toBe('create')
        ->and($cr->status)->toBe('pending')
        ->and($cr->payload['title'])->toBe('My Community Note');
});

test('accounts less than 24h old are blocked with Bangla message', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->actingAs($user)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Spam Note',
            'content' => 'Test',
        ]);

    $response->assertSessionHasErrors([
        'account_age' => 'আপনার অ্যাকাউন্টটি নতুন। স্প্যাম রোধে অ্যাকাউন্ট তৈরির ২৪ ঘণ্টা পর রিসোর্স আপলোড করা যাবে।',
    ]);

    expect(ResourceChangeRequest::count())->toBe(0);
});

test('moderator directly creates resource without staging and bypasses age check', function () {
    [, $node] = createTestSubjectAndNode();

    $moderator = User::factory()->create([
        'created_at' => now()->subMinutes(10), // even if new
    ]);
    $moderator->givePermissionTo('moderate resources');

    $this->actingAs($moderator)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Direct Moderator Resource',
            'content' => 'Immediate publish',
        ])
        ->assertSessionHas('success', 'Resource created successfully.');

    expect(Resource::count())->toBe(1);
    expect(ResourceChangeRequest::count())->toBe(0);
    expect(Resource::first()->title)->toBe('Direct Moderator Resource');
});

test('unverified user cannot exceed quota of 30 pending requests', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'is_verified' => false,
        'created_at' => now()->subDays(2),
    ]);

    // Create 30 existing pending change requests
    for ($i = 0; $i < 30; $i++) {
        ResourceChangeRequest::recordCreate($user->id, $node->id, [
            'title' => "Resource {$i}",
            'resource_type' => 'note',
        ]);
    }

    $response = $this->actingAs($user)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => '31st Resource',
            'content' => 'Exceed limit',
        ]);

    $response->assertSessionHasErrors('pending_limit');
    expect(ResourceChangeRequest::count())->toBe(30);
});

test('verified user can upload beyond 30 pending requests up to 100', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'is_verified' => true,
        'created_at' => now()->subDays(2),
    ]);

    // Create 30 existing pending change requests
    for ($i = 0; $i < 30; $i++) {
        ResourceChangeRequest::recordCreate($user->id, $node->id, [
            'title' => "Resource {$i}",
            'resource_type' => 'note',
        ]);
    }

    $this->actingAs($user)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => '31st Resource',
            'content' => 'Allowed for verified',
        ])
        ->assertSessionHas('success', 'Resource submitted for moderation.');

    expect(ResourceChangeRequest::count())->toBe(31);
});

test('frozen node rejects upload for non-moderators', function () {
    [, $node] = createTestSubjectAndNode(isFrozen: true);

    $user = User::factory()->create([
        'created_at' => now()->subDays(2),
    ]);

    $this->actingAs($user)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Locked Folder Resource',
            'content' => 'Should fail',
        ])
        ->assertForbidden();

    expect(ResourceChangeRequest::count())->toBe(0);
});

test('moderator can upload to frozen node', function () {
    [, $node] = createTestSubjectAndNode(isFrozen: true);

    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $this->actingAs($moderator)
        ->post('/resources', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Moderator Bypass Frozen',
            'content' => 'Allowed',
        ])
        ->assertSessionHas('success', 'Resource created successfully.');

    expect(Resource::count())->toBe(1);
});

test('bulk image upload stages multiple change requests for non-moderator', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'created_at' => now()->subDays(2),
    ]);

    $file1 = UploadedFile::fake()->create('page1.png', 100, 'image/png');
    $file2 = UploadedFile::fake()->create('page2.png', 100, 'image/png');

    $this->actingAs($user)
        ->post('/resources/bulk/images', [
            'node_id' => $node->id,
            'files' => [$file1, $file2],
            'custom_titles' => ['Image Page 1', 'Image Page 2'],
        ])
        ->assertSessionHas('success', 'Images submitted for moderation.');

    expect(Resource::count())->toBe(0);
    expect(ResourceChangeRequest::count())->toBe(2);
    expect(ResourceChangeRequest::pluck('status')->all())->toBe(['pending', 'pending']);
});

test('my-submissions page returns list and quota', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create();
    ResourceChangeRequest::recordCreate($user->id, $node->id, [
        'title' => 'Pending Resource',
        'resource_type' => 'note',
    ]);

    $this->actingAs($user)
        ->get('/my-submissions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Submissions/Index')
            ->has('submissions.data', 1)
            ->where('quota.used', 1)
            ->where('quota.max', 30)
            ->where('counts.pending', 1)
        );
});

test('moderation notification generates public url on approve and my-submissions url on reject', function () {
    [$subject, $node] = createTestSubjectAndNode();
    $user = User::factory()->create();

    $cr = ResourceChangeRequest::recordCreate($user->id, $node->id, [
        'title' => 'Physics Formula Note',
        'resource_type' => 'note',
    ]);

    // Approved notification
    $approvedNotif = new ResourceModerationNotification($cr, 'approved');
    $approvedData = $approvedNotif->toArray($user);
    expect($approvedData['url'])->toBe("/subjects/{$subject->slug}/nodes/{$node->slug}");

    // Rejected notification
    $rejectedNotif = new ResourceModerationNotification($cr, 'rejected', feedback: 'Irrelevant topic');
    $rejectedData = $rejectedNotif->toArray($user);
    expect($rejectedData['url'])->toBe('/my-submissions?status=rejected');
});

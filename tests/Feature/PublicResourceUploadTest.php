<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('s3');
    Permission::findOrCreate('view admin', 'web');
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

test('guest cannot upload documents', function () {
    [, $node] = createTestSubjectAndNode();

    $this->post('/resources/documents', [
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Test Note',
        'items' => [
            ['format' => 'image', 'source' => 'resources/notes/test.webp'],
        ],
    ])->assertRedirect('/login');
});

test('banned user cannot upload resources', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'banned_until' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)
        ->post('/resources/documents', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Banned User Note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/test.webp'],
            ],
        ]);

    $response->assertSessionHasErrors('banned');
    expect(Resource::count())->toBe(0);
});

test('public authenticated user submits document with pending status', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/resources/documents', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'My Community Note',
            'description' => 'Detailed notes',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/uuid-1.webp'],
                ['format' => 'image', 'source' => 'resources/notes/uuid-2.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Resource::count())->toBe(1);

    $res = Resource::first();
    expect($res->user_id)->toBe($user->id)
        ->and($res->node_id)->toBe($node->id)
        ->and($res->status)->toBe('pending')
        ->and($res->title)->toBe('My Community Note')
        ->and($res->items()->count())->toBe(2);
});

test('moderator user uploading document gets auto-approved', function () {
    [, $node] = createTestSubjectAndNode();

    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $this->actingAs($moderator)
        ->post('/resources/documents', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Moderator Note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/mod.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $res = Resource::first();
    expect($res->status)->toBe('approved')
        ->and($res->reviewed_by)->toBe($moderator->id)
        ->and($res->reviewed_at)->not->toBeNull();
});

test('regular users are blocked after 20 pending submissions while verified can submit up to 50', function () {
    [, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'is_verified' => false,
    ]);

    for ($i = 0; $i < 20; $i++) {
        Resource::create([
            'user_id' => $user->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => "Pending Note {$i}",
            'status' => 'pending',
        ]);
    }

    // 21st submission should be rejected
    $this->actingAs($user)
        ->post('/resources/documents', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Exceeding Quota',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/extra.webp'],
            ],
        ])
        ->assertSessionHasErrors('pending_limit');

    // Make user verified - now limit is 50
    $user->update(['is_verified' => true]);

    $this->actingAs($user->fresh())
        ->post('/resources/documents', [
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Allowed For Verified',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/allowed.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});
